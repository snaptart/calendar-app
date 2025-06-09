<?php
/**
 * API Client Service - Centralized backend communication
 * Location: frontend/services/ApiClient.php
 * 
 * Handles all communication with backend/api.php
 * Provides abstraction layer between frontend and backend
 */

class ApiClient {
    private $baseUrl;
    private $timeout;
    private $lastError;
    private $debug;
    
    /**
     * Constructor
     */
    public function __construct($config = []) {
        $this->baseUrl = $config['baseUrl'] ?? $this->detectBaseUrl();
        $this->timeout = $config['timeout'] ?? 30;
        $this->debug = $config['debug'] ?? false;
        $this->lastError = null;
    }
    
    /**
     * Auto-detect base URL for API
     */
    private function detectBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = dirname($_SERVER['SCRIPT_NAME']);
        
        // Navigate to backend from frontend directory
        $apiPath = str_replace('/frontend/pages', '/backend/api.php', $path);
        $apiPath = str_replace('/frontend', '/backend/api.php', $apiPath);
        
        return $protocol . $host . $apiPath;
    }
    
    /**
     * Make HTTP request to backend API
     */
    private function makeRequest($method, $endpoint = '', $data = null, $options = []) {
        $url = $this->baseUrl . $endpoint;
        
        if ($this->debug) {
            error_log("ApiClient: {$method} {$url}");
            if ($data) {
                error_log("ApiClient Data: " . print_r($data, true));
            }
        }
        
        // Initialize cURL
        $ch = curl_init();
        
        // Set basic cURL options
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false, // For local development
            CURLOPT_SSL_VERIFYHOST => false, // For local development
            CURLOPT_COOKIEFILE => '', // Enable cookie handling
            CURLOPT_COOKIEJAR => '', // Enable cookie handling
        ]);
        
        // Set method-specific options
        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            case 'GET':
            default:
                // GET is default
                break;
        }
        
        // Handle data based on content type
        if ($data !== null) {
            if (isset($options['multipart']) && $options['multipart']) {
                // Multipart form data (for file uploads)
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            } else {
                // JSON data
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen(json_encode($data))
                ]);
            }
        }
        
        // Forward session cookies if available
        if (isset($_COOKIE)) {
            $cookieString = '';
            foreach ($_COOKIE as $name => $value) {
                $cookieString .= $name . '=' . $value . '; ';
            }
            curl_setopt($ch, CURLOPT_COOKIE, rtrim($cookieString, '; '));
        }
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        // Handle cURL errors
        if ($error) {
            $this->lastError = "cURL Error: {$error}";
            if ($this->debug) {
                error_log("ApiClient cURL Error: {$error}");
            }
            throw new Exception($this->lastError);
        }
        
        // Handle HTTP errors
        if ($httpCode >= 400) {
            $errorData = json_decode($response, true);
            $errorMessage = isset($errorData['error']) ? $errorData['error'] : "HTTP {$httpCode}";
            $this->lastError = $errorMessage;
            
            if ($this->debug) {
                error_log("ApiClient HTTP Error {$httpCode}: {$errorMessage}");
            }
            
            // Handle specific HTTP codes
            if ($httpCode === 401) {
                throw new UnauthorizedException($errorMessage);
            } elseif ($httpCode === 403) {
                throw new ForbiddenException($errorMessage);
            } elseif ($httpCode === 404) {
                throw new NotFoundException($errorMessage);
            } else {
                throw new ApiException($errorMessage, $httpCode);
            }
        }
        
        // Parse JSON response
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->lastError = "Invalid JSON response: " . json_last_error_msg();
            if ($this->debug) {
                error_log("ApiClient JSON Error: {$this->lastError}");
                error_log("Raw Response: {$response}");
            }
            throw new Exception($this->lastError);
        }
        
        if ($this->debug) {
            error_log("ApiClient Response: " . print_r($decoded, true));
        }
        
        return $decoded;
    }
    
    // ========================================================================
    // AUTHENTICATION METHODS
    // ========================================================================
    
    /**
     * Check authentication status
     */
    public function checkAuth() {
        return $this->makeRequest('GET', '?action=check_auth');
    }
    
    /**
     * Login user
     */
    public function login($email, $password, $rememberMe = false) {
        return $this->makeRequest('POST', '', [
            'action' => 'login',
            'email' => $email,
            'password' => $password,
            'rememberMe' => $rememberMe
        ]);
    }
    
    /**
     * Register new user
     */
    public function register($name, $email, $password) {
        return $this->makeRequest('POST', '', [
            'action' => 'register',
            'name' => $name,
            'email' => $email,
            'password' => $password
        ]);
    }
    
    /**
     * Logout user
     */
    public function logout() {
        return $this->makeRequest('POST', '', [
            'action' => 'logout'
        ]);
    }
    
    // ========================================================================
    // USER METHODS
    // ========================================================================
    
    /**
     * Get all users
     */
    public function getUsers() {
        return $this->makeRequest('GET', '?action=users');
    }
    
    /**
     * Get users with statistics
     */
    public function getUsersWithStats() {
        return $this->makeRequest('GET', '?action=users_with_stats');
    }
    
    /**
     * Search users
     */
    public function searchUsers($query, $limit = 10) {
        return $this->makeRequest('POST', '', [
            'action' => 'search_users',
            'query' => $query,
            'limit' => $limit
        ]);
    }
    
    /**
     * Get user activity
     */
    public function getUserActivity($days = 30) {
        return $this->makeRequest('GET', "?action=user_activity&days={$days}");
    }
    
    /**
     * Update user profile
     */
    public function updateUserProfile($data) {
        return $this->makeRequest('POST', '', array_merge([
            'action' => 'update_user_profile'
        ], $data));
    }
    
    // ========================================================================
    // EVENT METHODS
    // ========================================================================
    
    /**
     * Get all events
     */
    public function getEvents($userIds = null) {
        $endpoint = '?action=events';
        if ($userIds && is_array($userIds)) {
            $endpoint .= '&user_ids=' . implode(',', $userIds);
        }
        return $this->makeRequest('GET', $endpoint);
    }
    
    /**
     * Get events by user
     */
    public function getEventsByUser($userId, $startDate = null, $endDate = null) {
        $endpoint = "?action=events_by_user&user_id={$userId}";
        if ($startDate) $endpoint .= "&start_date={$startDate}";
        if ($endDate) $endpoint .= "&end_date={$endDate}";
        
        return $this->makeRequest('GET', $endpoint);
    }
    
    /**
     * Get events in date range
     */
    public function getEventsInRange($startDate, $endDate, $userIds = null) {
        $endpoint = "?action=events_range&start_date={$startDate}&end_date={$endDate}";
        if ($userIds && is_array($userIds)) {
            $endpoint .= '&user_ids=' . implode(',', $userIds);
        }
        return $this->makeRequest('GET', $endpoint);
    }
    
    /**
     * Get upcoming events
     */
    public function getUpcomingEvents($limit = 10) {
        return $this->makeRequest('GET', "?action=upcoming_events&limit={$limit}");
    }
    
    /**
     * Search events
     */
    public function searchEvents($query, $userId = null, $limit = 20) {
        $endpoint = "?action=search_events&query=" . urlencode($query) . "&limit={$limit}";
        if ($userId) $endpoint .= "&user_id={$userId}";
        
        return $this->makeRequest('GET', $endpoint);
    }
    
    /**
     * Create new event
     */
    public function createEvent($title, $start, $end = null) {
        return $this->makeRequest('POST', '', [
            'action' => 'create_event',
            'title' => $title,
            'start' => $start,
            'end' => $end
        ]);
    }
    
    /**
     * Update existing event
     */
    public function updateEvent($eventId, $data) {
        return $this->makeRequest('PUT', '', array_merge([
            'id' => $eventId
        ], $data));
    }
    
    /**
     * Delete event
     */
    public function deleteEvent($eventId) {
        return $this->makeRequest('DELETE', "?id={$eventId}");
    }
    
    /**
     * Get event statistics
     */
    public function getEventStats($userId = null) {
        $endpoint = '?action=event_stats';
        if ($userId) $endpoint .= "&user_id={$userId}";
        
        return $this->makeRequest('GET', $endpoint);
    }
    
    // ========================================================================
    // IMPORT METHODS
    // ========================================================================
    
    /**
     * Get supported import formats
     */
    public function getImportFormats() {
        return $this->makeRequest('GET', '?action=import_formats');
    }
    
    /**
     * Validate import file
     */
    public function validateImportFile($filePath, $originalName) {
        $data = [
            'action' => 'validate_import_file',
            'import_file' => new CURLFile($filePath, mime_content_type($filePath), $originalName)
        ];
        
        return $this->makeRequest('POST', '', $data, ['multipart' => true]);
    }
    
    /**
     * Preview import file
     */
    public function previewImportFile($filePath, $originalName) {
        $data = [
            'action' => 'preview_import',
            'import_file' => new CURLFile($filePath, mime_content_type($filePath), $originalName)
        ];
        
        return $this->makeRequest('POST', '', $data, ['multipart' => true]);
    }
    
    /**
     * Import events from file
     */
    public function importEvents($filePath, $originalName) {
        $data = [
            'action' => 'import_events',
            'import_file' => new CURLFile($filePath, mime_content_type($filePath), $originalName)
        ];
        
        return $this->makeRequest('POST', '', $data, ['multipart' => true]);
    }
    
    // ========================================================================
    // SYSTEM METHODS
    // ========================================================================
    
    /**
     * Test API connection
     */
    public function testConnection() {
        return $this->makeRequest('GET', '?action=test');
    }
    
    /**
     * Get calendar updates (for SSE alternative)
     */
    public function getCalendarUpdates($lastId = 0, $limit = 10) {
        return $this->makeRequest('GET', "?action=calendar_updates&last_id={$lastId}&limit={$limit}");
    }
    
    /**
     * Get latest update ID
     */
    public function getLatestUpdateId() {
        return $this->makeRequest('GET', '?action=latest_update_id');
    }
    
    /**
     * Get database statistics
     */
    public function getDatabaseStats() {
        return $this->makeRequest('GET', '?action=database_stats');
    }
    
    /**
     * Broadcast notification
     */
    public function broadcastNotification($message, $type = 'info', $data = []) {
        return $this->makeRequest('POST', '', [
            'action' => 'broadcast_notification',
            'message' => $message,
            'type' => $type,
            'data' => $data
        ]);
    }
    
    // ========================================================================
    // UTILITY METHODS
    // ========================================================================
    
    /**
     * Get last error message
     */
    public function getLastError() {
        return $this->lastError;
    }
    
    /**
     * Enable debug mode
     */
    public function enableDebug($enable = true) {
        $this->debug = $enable;
        return $this;
    }
    
    /**
     * Set timeout
     */
    public function setTimeout($seconds) {
        $this->timeout = $seconds;
        return $this;
    }
    
    /**
     * Set base URL
     */
    public function setBaseUrl($url) {
        $this->baseUrl = rtrim($url, '/');
        return $this;
    }
    
    /**
     * Validate required fields in data
     */
    private function validateRequired($data, $requiredFields) {
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || 
                (is_string($data[$field]) && trim($data[$field]) === '')) {
                throw new InvalidArgumentException("Missing required field: {$field}");
            }
        }
    }
    
    /**
     * Format datetime for API
     */
    public static function formatDateTime($dateTime) {
        if ($dateTime instanceof DateTime) {
            return $dateTime->format('Y-m-d H:i:s');
        }
        
        if (is_string($dateTime)) {
            $dt = new DateTime($dateTime);
            return $dt->format('Y-m-d H:i:s');
        }
        
        return null;
    }
    
    /**
     * Create API client instance with default configuration
     */
    public static function create($config = []) {
        return new self($config);
    }
    
    /**
     * Create authenticated API client
     * Throws exception if not authenticated
     */
    public static function createAuthenticated($config = []) {
        $client = new self($config);
        
        try {
            $authResult = $client->checkAuth();
            if (!$authResult['authenticated']) {
                throw new UnauthorizedException('Authentication required');
            }
        } catch (Exception $e) {
            throw new UnauthorizedException('Authentication check failed: ' . $e->getMessage());
        }
        
        return $client;
    }
}

// ============================================================================
// CUSTOM EXCEPTIONS
// ============================================================================

/**
 * Base API exception
 */
class ApiException extends Exception {
    protected $httpCode;
    
    public function __construct($message, $httpCode = 500, Exception $previous = null) {
        parent::__construct($message, 0, $previous);
        $this->httpCode = $httpCode;
    }
    
    public function getHttpCode() {
        return $this->httpCode;
    }
}

/**
 * Unauthorized exception (401)
 */
class UnauthorizedException extends ApiException {
    public function __construct($message = 'Authentication required', Exception $previous = null) {
        parent::__construct($message, 401, $previous);
    }
}

/**
 * Forbidden exception (403)
 */
class ForbiddenException extends ApiException {
    public function __construct($message = 'Access forbidden', Exception $previous = null) {
        parent::__construct($message, 403, $previous);
    }
}

/**
 * Not found exception (404)
 */
class NotFoundException extends ApiException {
    public function __construct($message = 'Resource not found', Exception $previous = null) {
        parent::__construct($message, 404, $previous);
    }
}