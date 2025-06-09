<?php
/**
 * Session Management Class for itmdev
 * Location: backend/auth/Session.php
 * 
 * Updated to work with the itmdev session table
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
            
            // Insert session record into itmdev session table
            $stmt = $this->pdo->prepare("
                INSERT INTO session 
                (php_Session_ID, user_ID, expires_at, ip_address, user_agent, remember_me, is_active, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, 1, 'calendar_system')
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
            
            // Get session with user data from itmdev tables
            $stmt = $this->pdo->prepare("
                SELECT s.*, u.user_ID as user_id, u.user_Name as name, 
                       u.user_Email as email, '#3498db' as color
                FROM session s
                JOIN user u ON s.user_ID = u.user_ID
                WHERE s.php_Session_ID = ? AND s.is_active = 1 AND s.expires_at > NOW()
                  AND u.user_Status = 'Active'
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
                UPDATE session 
                SET is_active = 0, session_End_Reason = 'logout', updated_by = 'calendar_system'
                WHERE php_Session_ID = ?
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
                UPDATE session 
                SET is_active = 0, session_End_Reason = 'logout_all', updated_by = 'calendar_system'
                WHERE user_ID = ?
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
                SELECT php_Session_ID as id, create_ts as created_at, expires_at, 
                       ip_address, user_agent, remember_me
                FROM session 
                WHERE user_ID = ? AND is_active = 1 AND expires_at > NOW()
                ORDER BY create_ts DESC
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
            // Mark expired sessions as inactive
            $stmt = $this->pdo->prepare("
                UPDATE session 
                SET is_active = 0, session_End_Reason = 'expired', updated_by = 'system_cleanup'
                WHERE expires_at < NOW() OR is_active = 0
            ");
            
            $stmt->execute();
            $updatedCount = $stmt->rowCount();
            
            // Actually delete very old sessions (older than 7 days)
            $deleteStmt = $this->pdo->prepare("
                DELETE FROM session 
                WHERE create_ts < DATE_SUB(NOW(), INTERVAL 7 DAY) AND is_active = 0
            ");
            
            $deleteStmt->execute();
            $deletedCount = $deleteStmt->rowCount();
            
            $totalCleaned = $updatedCount + $deletedCount;
            
            if ($totalCleaned > 0) {
                error_log("Cleaned up {$totalCleaned} expired sessions ({$updatedCount} marked inactive, {$deletedCount} deleted)");
            }
            
            return $totalCleaned;
            
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
                UPDATE session 
                SET updated_by = 'activity_touch'
                WHERE php_Session_ID = ? AND create_ts < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
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
    
    /**
     * Get session statistics
     * 
     * @return array Session statistics
     */
    public function getSessionStats() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    COUNT(*) as total_sessions,
                    COUNT(CASE WHEN is_active = 1 AND expires_at > NOW() THEN 1 END) as active_sessions,
                    COUNT(CASE WHEN remember_me = 1 THEN 1 END) as remember_me_sessions,
                    COUNT(CASE WHEN create_ts >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as recent_sessions
                FROM session
            ");
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            error_log("Session stats error: " . $e->getMessage());
            return [
                'total_sessions' => 0,
                'active_sessions' => 0,
                'remember_me_sessions' => 0,
                'recent_sessions' => 0
            ];
        }
    }
}
?>