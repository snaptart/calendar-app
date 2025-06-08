<?php
/**
 * Authentication Class for Collaborative Calendar
 * Location: backend/auth/Auth.php
 * 
 * Handles user authentication, registration, and session management
 */

require_once __DIR__ . '/Session.php';

class Auth {
    private $pdo;
    private $session;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->session = new Session($pdo);
    }
    
    /**
     * Register a new user
     * 
     * @param string $name User's full name
     * @param string $email User's email address
     * @param string $password Plain text password
     * @return array Result array with success/error
     */
    public function register($name, $email, $password) {
        try {
            // Validate input
            $validation = $this->validateRegistrationInput($name, $email, $password);
            if (!$validation['valid']) {
                return ['success' => false, 'error' => $validation['error']];
            }
            
            // Check if user already exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'An account with this email already exists'];
            }
            
            // Check if name is already taken
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE name = ?");
            $stmt->execute([$name]);
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'This name is already taken. Please choose a different name.'];
            }
            
            // Generate user color
            $colors = ['#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#34495e', '#e67e22', '#3498db'];
            $color = $colors[array_rand($colors)];
            
            // Hash password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $this->pdo->prepare("
                INSERT INTO users (name, email, password_hash, color) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$name, $email, $passwordHash, $color]);
            
            $userId = $this->pdo->lastInsertId();
            
            return [
                'success' => true,
                'message' => 'Account created successfully',
                'user' => [
                    'id' => $userId,
                    'name' => $name,
                    'email' => $email,
                    'color' => $color
                ]
            ];
            
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            
            // Check for duplicate key errors
            if ($e->getCode() == 23000) {
                if (strpos($e->getMessage(), 'email') !== false) {
                    return ['success' => false, 'error' => 'An account with this email already exists'];
                } elseif (strpos($e->getMessage(), 'name') !== false) {
                    return ['success' => false, 'error' => 'This name is already taken'];
                }
            }
            
            return ['success' => false, 'error' => 'Registration failed. Please try again.'];
        }
    }
    
    /**
     * Login user
     * 
     * @param string $email User's email
     * @param string $password Plain text password
     * @param bool $rememberMe Whether to create persistent session
     * @return array Result array with success/error
     */
    public function login($email, $password, $rememberMe = false) {
        try {
            // Validate input
            if (!$this->validateEmail($email) || empty($password)) {
                return ['success' => false, 'error' => 'Please provide valid email and password'];
            }
            
            // Find user by email
            $stmt = $this->pdo->prepare("
                SELECT id, name, email, password_hash, color, last_login 
                FROM users 
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'error' => 'Invalid email or password'];
            }
            
            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'error' => 'Invalid email or password'];
            }
            
            // Update last login
            $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Create session
            $sessionId = $this->session->create($user['id'], $rememberMe);
            
            // Set session cookie
            $this->setSessionCookie($sessionId, $rememberMe);
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'color' => $user['color']
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Login failed. Please try again.'];
        }
    }
    
    /**
     * Check if user is authenticated
     * 
     * @return array Result with authentication status and user data
     */
    public function checkAuth() {
        try {
            $sessionId = $_COOKIE['calendar_session'] ?? null;
            
            if (!$sessionId) {
                return ['authenticated' => false, 'error' => 'No session found'];
            }
            
            $user = $this->session->validate($sessionId);
            
            if (!$user) {
                // Clean up invalid session cookie
                $this->clearSessionCookie();
                return ['authenticated' => false, 'error' => 'Invalid or expired session'];
            }
            
            return [
                'authenticated' => true,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'color' => $user['color']
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Auth check error: " . $e->getMessage());
            return ['authenticated' => false, 'error' => 'Authentication check failed'];
        }
    }
    
    /**
     * Logout user
     * 
     * @return array Result array
     */
    public function logout() {
        try {
            $sessionId = $_COOKIE['calendar_session'] ?? null;
            
            if ($sessionId) {
                $this->session->destroy($sessionId);
            }
            
            $this->clearSessionCookie();
            
            return ['success' => true, 'message' => 'Logged out successfully'];
            
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Logout failed'];
        }
    }
    
    /**
     * Get current authenticated user
     * 
     * @return array|null User data or null if not authenticated
     */
    public function getCurrentUser() {
        $auth = $this->checkAuth();
        return $auth['authenticated'] ? $auth['user'] : null;
    }
    
    /**
     * Middleware to require authentication
     * 
     * @return array|null User data if authenticated, null if not
     */
    public function requireAuth() {
        $auth = $this->checkAuth();
        
        if (!$auth['authenticated']) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit();
        }
        
        return $auth['user'];
    }
    
    /**
     * Validate registration input
     * 
     * @param string $name
     * @param string $email
     * @param string $password
     * @return array Validation result
     */
    private function validateRegistrationInput($name, $email, $password) {
        if (empty($name) || strlen(trim($name)) < 2) {
            return ['valid' => false, 'error' => 'Name must be at least 2 characters long'];
        }
        
        if (!$this->validateEmail($email)) {
            return ['valid' => false, 'error' => 'Please provide a valid email address'];
        }
        
        if (empty($password) || strlen($password) < 6) {
            return ['valid' => false, 'error' => 'Password must be at least 6 characters long'];
        }
        
        // Check for potentially harmful characters
        if (preg_match('/[<>"\']/', $name)) {
            return ['valid' => false, 'error' => 'Name contains invalid characters'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Validate email format
     * 
     * @param string $email
     * @return bool
     */
    private function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Set session cookie
     * 
     * @param string $sessionId
     * @param bool $rememberMe
     */
    private function setSessionCookie($sessionId, $rememberMe = false) {
        $expiry = $rememberMe ? time() + (30 * 24 * 60 * 60) : 0; // 30 days or session
        
        setcookie('calendar_session', $sessionId, [
            'expires' => $expiry,
            'path' => '/',
            'domain' => '', // Use current domain
            'secure' => isset($_SERVER['HTTPS']), // Only over HTTPS if available
            'httponly' => true, // Prevent XSS
            'samesite' => 'Strict' // CSRF protection
        ]);
    }
    
    /**
     * Clear session cookie
     */
    private function clearSessionCookie() {
        setcookie('calendar_session', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }
}
?>