<?php
/**
 * SPA API Endpoints
 * Handles Single Page Application specific requests
 */

// Set proper error handling to prevent HTML output
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Set headers first
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Start output buffering to capture any unwanted output
ob_start();

try {
    require_once 'database/config.php';
    require_once 'auth/Auth.php';
    require_once 'helpers/spa-helpers.php';
} catch (Exception $e) {
    // Clean any output buffer and send error
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Server configuration error: ' . $e->getMessage()]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

// Initialize auth
try {
    $auth = new Auth($pdo);
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Authentication initialization error: ' . $e->getMessage()]);
    exit();
}

// Handle preflight requests
if ($method === 'OPTIONS') {
    ob_clean();
    http_response_code(200);
    exit();
}

/**
 * Send JSON response
 */
function sendResponse($data, $statusCode = 200) {
    // Clear any output buffer
    if (ob_get_level()) {
        ob_clean();
    }
    
    http_response_code($statusCode);
    echo json_encode($data);
}

/**
 * Get JSON input from request body
 */
function getJsonInput() {
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }
    return $input;
}

/**
 * Error handling wrapper
 */
function handleRequest($callback) {
    try {
        // Clear any unwanted output
        if (ob_get_level()) {
            ob_clean();
        }
        
        $callback();
    } catch (Exception $e) {
        // Clear any output buffer
        if (ob_get_level()) {
            ob_clean();
        }
        
        error_log("SPA API Error: " . $e->getMessage());
        
        // Determine appropriate HTTP status code
        $statusCode = 500;
        if (strpos($e->getMessage(), 'not found') !== false) {
            $statusCode = 404;
        } elseif (strpos($e->getMessage(), 'required') !== false || 
                  strpos($e->getMessage(), 'invalid') !== false ||
                  strpos($e->getMessage(), 'missing') !== false) {
            $statusCode = 400;
        } elseif (strpos($e->getMessage(), 'permission') !== false ||
                  strpos($e->getMessage(), 'unauthorized') !== false) {
            $statusCode = 403;
        }
        
        http_response_code($statusCode);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Route the request
handleRequest(function() use ($method, $auth) {
    
    switch ($method) {
        case 'GET':
            handleGetRequest($auth);
            break;
        case 'POST':
            handlePostRequest($auth);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
});

/**
 * Handle GET requests
 */
function handleGetRequest($auth) {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        // SPA Support - Get page content
        case 'get_page_content':
            $auth->requireAuth();
            
            $page = $_GET['page'] ?? 'calendar';
            $allowedPages = ['calendar', 'events', 'users', 'import'];
            
            if (!in_array($page, $allowedPages)) {
                throw new Exception('Invalid page requested');
            }
            
            // Get page content and configuration
            $pageContent = getSPAPageContent($page);
            sendResponse($pageContent);
            break;
            
        // SPA Support - Get page configuration  
        case 'get_page_config':
            $auth->requireAuth();
            
            $page = $_GET['page'] ?? 'calendar';
            $pageConfig = getSPAPageConfig($page);
            sendResponse($pageConfig);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
}

/**
 * Handle POST requests
 */
function handlePostRequest($auth) {
    $input = getJsonInput();
    $action = $input['action'] ?? '';
    
    switch ($action) {
        // SPA Support - Get dependencies
        case 'get_dependencies':
            $auth->requireAuth();
            
            if (!isset($input['dependencies']) || !is_array($input['dependencies'])) {
                throw new Exception('Missing dependencies field');
            }
            
            $dependencies = getSPADependencies($input['dependencies']);
            sendResponse($dependencies);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
}

// End output buffering and send response
ob_end_flush();
?>