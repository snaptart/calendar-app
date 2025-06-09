<?php
/**
 * Authentication Service - Frontend authentication management
 * Location: frontend/services/AuthService.php
 * 
 * Provides authentication utilities and session management for frontend
 */

require_once __DIR__ . '/ApiClient.php';

class AuthService {
    private $apiClient;
    private $currentUser;
    private $sessionKey = 'calendar_auth_user';
    
    /**
     * Constructor
     */
    public function __construct(ApiClient $apiClient = null) {
        $this->apiClient = $apiClient ?: ApiClient::create();
        $this->initializeSession();
        $this->loadCurrentUser();
    }
    
    /**
     * Initialize session if not already started
     */
    private function initializeSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Load current user from session or API
     */
    private function loadCurrentUser() {
        // First check session
        if (isset($_SESSION[$this->sessionKey])) {
            $this->currentUser = $_SESSION[$this->sessionKey];
        }
        
        // Verify with backend if we have session data
        if ($this->currentUser) {
            try {
                $authResult = $this->apiClient->checkAuth();
                if ($authResult['authenticated']) {
                    $this->currentUser = $authResult['user'];
                    $this->updateSession($this->currentUser);
                } else {
                    $this->clearSession();
                }
            } catch (Exception $e) {
                // If auth check fails, clear session
                $this->clearSession();
            }
        }
    }
    
    /**
     * Update session with user data
     */
    private function updateSession($user) {
        $_SESSION[$this->sessionKey] = $user;
        $this->currentUser = $user;
    }
    
    /**
     * Clear session data
     */
    private function clearSession() {
        unset($_SESSION[$this->sessionKey]);
        $this->currentUser = null;
    }
    
    /**
     * Check if user is authenticated
     */
    public function isAuthenticated() {
        try {
            $authResult = $this->apiClient->checkAuth();
            
            if ($authResult['authenticated']) {
                $this->updateSession($authResult['user']);
                return true;
            } else {
                $this->clearSession();
                return false;
            }
        } catch (Exception $e) {
            $this->clearSession();
            return false;
        }
    }
    
    /**
     * Require authentication - redirect if not authenticated
     */
    public function requireAuth($redirectUrl = null) {
        if (!$this->isAuthenticated()) {
            $this->redirectToLogin($redirectUrl);
            exit;
        }
        
        return $this->currentUser;
    }
    
    /**
     * Get current authenticated user
     */
    public function getCurrentUser() {
        return $this->currentUser;
    }
    
    /**
     * Get current user ID
     */
    public function getCurrentUserId() {
        return $this->currentUser ? $this->currentUser['id'] : null;
    }
    
    /**
     * Get current user name
     */
    public function getCurrentUserName() {
        return $this->currentUser ? $this->currentUser['name'] : null;
    }
    
    /**
     * Check if current user owns an event
     */
    public function canEditEvent($event) {
        if (!$this->currentUser) {
            return false;
        }
        
        $eventUserId = null;
        
        // Handle different event data structures
        if (is_array($event)) {
            $eventUserId = $event['user_id'] ?? $event['extendedProps']['userId'] ?? null;
        } elseif (is_object($event)) {
            $eventUserId = $event->user_id ?? $event->extendedProps->userId ?? null;
        }
        
        return $eventUserId && $eventUserId == $this->currentUser['id'];
    }
    
    /**
     * Login user
     */
    public function login($email, $password, $rememberMe = false) {
        try {
            $result = $this->apiClient->login($email, $password, $rememberMe);
            
            if ($result['success']) {
                // Re-check auth to get user data
                if ($this->isAuthenticated()) {
                    return [
                        'success' => true,
                        'message' => 'Login successful',
                        'user' => $this->currentUser
                    ];
                }
            }
            
            return $result;
        } catch (UnauthorizedException $e) {
            return [
                'success' => false,
                'error' => 'Invalid email or password'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Register new user
     */
    public function register($name, $email, $password) {
        try {
            return $this->apiClient->register($name, $email, $password);
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        try {
            $result = $this->apiClient->logout();
            $this->clearSession();
            
            // Destroy session if it's empty
            if (empty($_SESSION)) {
                session_destroy();
            }
            
            return $result;
        } catch (Exception $e) {
            // Even if API call fails, clear local session
            $this->clearSession();
            return ['success' => true];
        }
    }
    
    /**
     * Redirect to login page
     */
    public function redirectToLogin($returnUrl = null) {
        $loginUrl = $this->getLoginUrl();
        
        if ($returnUrl) {
            $loginUrl .= '?return=' . urlencode($returnUrl);
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $loginUrl .= '?return=' . urlencode($_SERVER['REQUEST_URI']);
        }
        
        header("Location: {$loginUrl}");
        exit;
    }
    
    /**
     * Get login URL
     */
    private function getLoginUrl() {
        // Try to determine login URL based on current location
        $currentDir = dirname($_SERVER['SCRIPT_NAME']);
        
        // If we're in a subdirectory, go back to pages directory
        if (strpos($currentDir, '/pages') !== false) {
            return './login.php';
        } else {
            return '/frontend/pages/login.php';
        }
    }
    
    /**
     * Redirect after successful login
     */
    public function redirectAfterLogin($defaultUrl = null) {
        $returnUrl = $_GET['return'] ?? $_POST['return'] ?? $defaultUrl;
        
        if ($returnUrl) {
            // Validate return URL to prevent redirect attacks
            if ($this->isValidReturnUrl($returnUrl)) {
                header("Location: {$returnUrl}");
                exit;
            }
        }
        
        // Default redirect to calendar
        header('Location: ./calendar.php');
        exit;
    }
    
    /**
     * Validate return URL to prevent redirect attacks
     */
    private function isValidReturnUrl($url) {
        // Only allow relative URLs or URLs to the same host
        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            $urlHost = parse_url($url, PHP_URL_HOST);
            $currentHost = $_SERVER['HTTP_HOST'];
            return $urlHost === $currentHost;
        }
        
        // Allow relative URLs
        return strpos($url, '/') === 0 || strpos($url, './') === 0;
    }
    
    /**
     * Get authentication status for JavaScript
     */
    public function getAuthStatusForJs() {
        if ($this->currentUser) {
            return [
                'authenticated' => true,
                'user' => [
                    'id' => $this->currentUser['id'],
                    'name' => $this->currentUser['name'],
                    'email' => $this->currentUser['email'],
                    'color' => $this->currentUser['color'] ?? '#3498db'
                ]
            ];
        }
        
        return ['authenticated' => false];
    }
    
    /**
     * Generate authentication JavaScript for pages
     */
    public function generateAuthJs() {
        $authStatus = $this->getAuthStatusForJs();
        
        return "
        window.CalendarAuth = " . json_encode($authStatus) . ";
        
        // Auto-redirect if not authenticated (except on login page)
        if (!window.CalendarAuth.authenticated && !window.location.pathname.includes('login.php')) {
            window.location.href = './login.php?return=' + encodeURIComponent(window.location.pathname + window.location.search);
        }
        
        // Set up global auth event handling
        document.addEventListener('DOMContentLoaded', function() {
            if (window.CalendarAuth.authenticated && window.CalendarAuth.user) {
                // Update user display elements
                const userNameInput = document.getElementById('userName');
                const userStatus = document.getElementById('userStatus');
                
                if (userNameInput) {
                    userNameInput.value = window.CalendarAuth.user.name;
                    userNameInput.disabled = true;
                    userNameInput.style.backgroundColor = '#f7fafc';
                }
                
                if (userStatus) {
                    userStatus.textContent = 'Logged in as: ' + window.CalendarAuth.user.name;
                    userStatus.className = 'status user-set';
                }
                
                // Add logout button if user section exists
                const userSection = document.querySelector('.user-section');
                if (userSection && !document.getElementById('logoutBtn')) {
                    const logoutBtn = document.createElement('button');
                    logoutBtn.id = 'logoutBtn';
                    logoutBtn.className = 'btn btn-small btn-outline';
                    logoutBtn.textContent = 'Logout';
                    logoutBtn.style.marginLeft = '8px';
                    
                    logoutBtn.addEventListener('click', function() {
                        if (confirm('Are you sure you want to logout?')) {
                            window.location.href = './logout.php';
                        }
                    });
                    
                    userSection.appendChild(logoutBtn);
                }
            }
        });
        ";
    }
    
    /**
     * Create authentication middleware for pages
     */
    public static function middleware($config = []) {
        return function() use ($config) {
            $authService = new self();
            
            $requireAuth = $config['requireAuth'] ?? true;
            $redirectUrl = $config['redirectUrl'] ?? null;
            
            if ($requireAuth) {
                return $authService->requireAuth($redirectUrl);
            }
            
            return $authService->getCurrentUser();
        };
    }
    
    /**
     * Create a simple authentication guard for pages
     */
    public static function guard($requireAuth = true) {
        $authService = new self();
        
        if ($requireAuth && !$authService->isAuthenticated()) {
            $authService->redirectToLogin();
        }
        
        return $authService;
    }
    
    /**
     * Get singleton instance
     */
    private static $instance;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
}