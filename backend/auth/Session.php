<?php
/**
 * Session Management Class for Collaborative Calendar
 * Location: backend/auth/Session.php
 * 
 * Handles secure session creation, validation, and cleanup
 */

class Session {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        
        // Clean up expired sessions periodically
        if (rand(1, 100) === 1) { // 1% chance
            $this->cleanupExpiredSessions();
        }
    }
    
    /**
     * Create a new session
     * 
     * @param int $userId User ID
     * @param bool $rememberMe Whether this is a persistent session
     * @return string Session ID
     */
    public function create($userId, $rememberMe = false) {
        try {
            // Generate secure session ID
            $sessionId = $this->generateSecureSessionId();
            
            // Calculate expiry
            $expiryTime = $rememberMe 
                ? date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)) // 30 days
                : date('Y-m-d H:i:s', time() + (24 * 60 * 60)); // 24 hours
            
            // Get client info
            $ipAddress = $this->getClientIP();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // Insert session record
            $stmt = $this->pdo->prepare("
                INSERT INTO user_sessions 
                (id, user_id, expires_at, ip_address, user_agent, remember_me, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            
            $stmt->execute([
                $sessionId,
                $userId,
                $expiryTime,
                $ipAddress,
                $userAgent,
                $rememberMe ? 1 : 0
            ]);
            
            error_log("Session created for user {$userId}: {$sessionId}");
            
            return $sessionId;
            
        } catch (Exception $e) {
            error_log("Session creation error: " . $e->getMessage());
            throw new Exception("Failed to create session");
        }
    }
    
    /**
     * Validate a session and return user data
     * 
     * @param string $sessionId Session ID to validate
     * @return array|null User data if valid, null if invalid
     */
    public function validate($sessionId) {
        try {
            if (empty($sessionId)) {
                return null;
            }
            
            // Get session with user data
            $stmt = $this->pdo->prepare("
                SELECT s.*, u.id as user_id, u.name, u.email, u.color 
                FROM user_sessions s
                JOIN users u ON s.user_id = u.id
                WHERE s.id = ? AND s.is_active = 1 AND s.expires_at > NOW()
            ");
            
            $stmt->execute([$sessionId]);
            $session = $stmt->fetch();
            
            if (!$session) {
                return null;
            }
            
            // Optional: Check IP address for additional security
            $currentIP = $this->getClientIP();
            if ($session['ip_address'] !== $currentIP) {
                error_log("Session IP mismatch for session {$sessionId}: stored={$session['ip_address']}, current={$currentIP}");
                // You might want to invalidate the session here for stricter security
                // For now, we'll just log it and allow the session
            }
            
            // Update session activity (optional)
            $this->touchSession($sessionId);
            
            return [
                'id' => $session['user_id'],
                'name' => $session['name'],
                'email' => $session['email'],
                'color' => $session['color']
            ];
            
        } catch (Exception $e) {
            error_log("Session validation error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Destroy a session
     * 
     * @param string $sessionId Session ID to destroy
     * @return bool Success status
     */
    public function destroy($sessionId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE user_sessions 
                SET is_active = 0 
                WHERE id = ?
            ");
            
            $stmt->execute([$sessionId]);
            
            error_log("Session destroyed: {$sessionId}");
            
            return true;
            
        } catch (Exception $e) {
            error_log("Session destruction error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Destroy all sessions for a user
     * 
     * @param int $userId User ID
     * @return bool Success status
     */
    public function destroyAllUserSessions($userId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE user_sessions 
                SET is_active = 0 
                WHERE user_id = ?
            ");
            
            $stmt->execute([$userId]);
            
            error_log("All sessions destroyed for user: {$userId}");
            
            return true;
            
        } catch (Exception $e) {
            error_log("User sessions destruction error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get active sessions for a user
     * 
     * @param int $userId User ID
     * @return array Array of session data
     */
    public function getUserSessions($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, created_at, expires_at, ip_address, user_agent, remember_me
                FROM user_sessions 
                WHERE user_id = ? AND is_active = 1 AND expires_at > NOW()
                ORDER BY created_at DESC
            ");
            
            $stmt->execute([$userId]);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get user sessions error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clean up expired sessions
     * 
     * @return int Number of sessions cleaned up
     */
    public function cleanupExpiredSessions() {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM user_sessions 
                WHERE expires_at < NOW() OR is_active = 0
            ");
            
            $stmt->execute();
            $deletedCount = $stmt->rowCount();
            
            if ($deletedCount > 0) {
                error_log("Cleaned up {$deletedCount} expired sessions");
            }
            
            return $deletedCount;
            
        } catch (Exception $e) {
            error_log("Session cleanup error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Update session activity timestamp (optional feature)
     * 
     * @param string $sessionId Session ID
     */
    private function touchSession($sessionId) {
        try {
            // Only update if more than 5 minutes have passed since creation
            // to avoid excessive database writes
            $stmt = $this->pdo->prepare("
                UPDATE user_sessions 
                SET created_at = created_at 
                WHERE id = ? AND created_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ");
            
            $stmt->execute([$sessionId]);
            
        } catch (Exception $e) {
            // Silently fail - this is not critical
            error_log("Session touch error: " . $e->getMessage());
        }
    }
    
    /**
     * Generate a cryptographically secure session ID
     * 
     * @return string Session ID
     */
    private function generateSecureSessionId() {
        // Generate 64 bytes of random data and hash it
        $randomBytes = random_bytes(64);
        $timestamp = microtime(true);
        $serverData = $_SERVER['HTTP_USER_AGENT'] ?? '' . $_SERVER['SERVER_NAME'] ?? '';
        
        return hash('sha256', $randomBytes . $timestamp . $serverData);
    }
    
    /**
     * Get client IP address (handles proxies)
     * 
     * @return string Client IP address
     */
    private function getClientIP() {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (array_map('trim', explode(',', $_SERVER[$key])) as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP, 
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        // Fallback to REMOTE_ADDR even if it's private (for local development)
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
?>