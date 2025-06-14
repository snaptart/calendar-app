<?php
/**
 * API Controller with Import Proxy - Routes import requests to worker
 * Location: backend/api.php
 * 
 * This version maintains the same frontend interface while routing
 * import functionality to the dedicated worker
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
    require_once 'models/User.php';
    require_once 'models/Event.php';
    require_once 'models/CalendarUpdate.php';
    require_once 'helpers/spa-helpers.php';
} catch (Exception $e) {
    // Clean any output buffer and send error
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Server configuration error: ' . $e->getMessage()]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

// Initialize models with error handling
try {
    $calendarUpdate = new CalendarUpdate($pdo);
    $userModel = new User($pdo, $calendarUpdate);
    $eventModel = new Event($pdo, $calendarUpdate);
    $auth = new Auth($pdo);

    // Initialize calendar updates system
    $calendarUpdate->initialize();
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Database initialization error: ' . $e->getMessage()]);
    exit();
}

// Handle preflight requests
if ($method === 'OPTIONS') {
    ob_clean();
    http_response_code(200);
    exit();
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
        
        error_log("API Error: " . $e->getMessage());
        
        // Determine appropriate HTTP status code
        $statusCode = 500;
        if (strpos($e->getMessage(), 'not found') !== false) {
            $statusCode = 404;
        } elseif (strpos($e->getMessage(), 'required') !== false || 
                  strpos($e->getMessage(), 'invalid') !== false ||
                  strpos($e->getMessage(), 'missing') !== false) {
            $statusCode = 400;
        } elseif (strpos($e->getMessage(), 'permission') !== false ||
                  strpos($e->getMessage(), 'own events') !== false ||
                  strpos($e->getMessage(), 'unauthorized') !== false) {
            $statusCode = 403;
        }
        
        http_response_code($statusCode);
        echo json_encode(['error' => $e->getMessage()]);
    }
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
 * Validate required fields in input
 */
function validateRequiredFields($input, $requiredFields) {
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || (is_string($input[$field]) && trim($input[$field]) === '')) {
            throw new Exception("Missing required field: {$field}");
        }
    }
}

/**
 * Proxy import requests to the import worker
 */
function proxyToImportWorker($auth) {
    // Verify authentication first
    $currentUser = $auth->requireAuth();
    
    // Build the URL to the import worker
    $workerUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . 
                 dirname($_SERVER['SCRIPT_NAME']) . '/workers/import.php';
    
    // Prepare the request to forward
    $postFields = $_POST;
    $files = $_FILES;
    
    // Create a new cURL request to the worker
    $ch = curl_init();
    
    // Prepare multipart form data
    $postData = [];
    
    // Add regular POST fields
    foreach ($postFields as $key => $value) {
        $postData[$key] = $value;
    }
    
    // Add files
    foreach ($files as $fieldName => $fileInfo) {
        if ($fileInfo['error'] === UPLOAD_ERR_OK) {
            $postData[$fieldName] = new CURLFile(
                $fileInfo['tmp_name'], 
                $fileInfo['type'], 
                $fileInfo['name']
            );
        }
    }
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $workerUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'User-Agent: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'Calendar-API-Proxy/1.0')
        ],
        // Forward cookies for authentication
        CURLOPT_COOKIE => $_SERVER['HTTP_COOKIE'] ?? '',
        CURLOPT_SSL_VERIFYPEER => false, // For local development
        CURLOPT_SSL_VERIFYHOST => false  // For local development
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        error_log("cURL Error when proxying to import worker: " . $error);
        throw new Exception('Failed to communicate with import service');
    }
    
    // Clean any output buffer
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Set the same status code as the worker response
    http_response_code($httpCode);
    
    // Return the worker's response directly
    echo $response;
    exit();
}

/**
 * Simple proxy using include (alternative method)
 */
function includeImportWorker($auth) {
    // Verify authentication first
    $currentUser = $auth->requireAuth();
    
    // Clean current output buffer
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Store current working directory
    $originalDir = getcwd();
    
    try {
        // Change to workers directory to make relative includes work
        chdir(__DIR__ . '/workers');
        
        // Include the import worker which will handle the request
        include 'import.php';
        
    } finally {
        // Restore original directory
        chdir($originalDir);
    }
    
    exit();
}

/**
 * Detect if this is an import request
 */
function isImportRequest() {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    // Check for multipart form data (file upload)
    if (strpos($contentType, 'multipart/form-data') !== false) {
        return true;
    }
    
    // Check for import actions in POST data
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $importActions = ['validate_import_file', 'import_events', 'preview_import', 'import_formats'];
        
        if (in_array($action, $importActions)) {
            return true;
        }
    }
    
    // Check for import actions in GET data
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        $importActions = ['import_formats', 'import_stats', 'supported_import_formats'];
        
        if (in_array($action, $importActions)) {
            return true;
        }
    }
    
    return false;
}

// Route the request
handleRequest(function() use ($pdo, $method, $auth, $userModel, $eventModel, $calendarUpdate) {
    
    // Check if this is an import request and proxy it
    if (isImportRequest()) {
        // Use the include method (simpler and more reliable for local development)
        includeImportWorker($auth);
        return;
    }
    
    switch ($method) {
        case 'GET':
            handleGetRequest($auth, $userModel, $eventModel, $calendarUpdate);
            break;
        case 'POST':
            handlePostRequest($auth, $userModel, $eventModel, $calendarUpdate);
            break;
        case 'PUT':
            handlePutRequest($auth, $eventModel);
            break;
        case 'DELETE':
            handleDeleteRequest($auth, $eventModel);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
});

/**
 * Handle GET requests
 */
function handleGetRequest($auth, $userModel, $eventModel, $calendarUpdate) {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'check_auth':
            $authResult = $auth->checkAuth();
            sendResponse($authResult);
            break;
            
        case 'users':
            $auth->requireAuth();
            $users = $userModel->getAllUsers();
            sendResponse($users);
            break;
            
        case 'users_with_stats':
            $auth->requireAuth();
            $users = $userModel->getAllUsersWithStats();
            sendResponse($users);
            break;
            
        case 'events':
            $auth->requireAuth();
            
            $userIds = $_GET['user_ids'] ?? '';
            $userIdsArray = null;
            
            if ($userIds) {
                $userIdsArray = array_filter(explode(',', $userIds), 'is_numeric');
                if (empty($userIdsArray)) {
                    sendResponse([]);
                    return;
                }
            }
            
            $events = $eventModel->getAllEvents($userIdsArray);
            sendResponse($events);
            break;
            
        case 'get_page_content':
            $auth->requireAuth();
            
            $page = $_GET['page'] ?? 'calendar';
            $allowedPages = ['calendar', 'events', 'users', 'import'];
            
            if (!in_array($page, $allowedPages)) {
                throw new Exception('Invalid page requested');
            }
            
            // Include SPA helpers if not already included
            require_once __DIR__ . '/helpers/spa-helpers.php';
            
            // Get page content and configuration
            $pageContent = getSPAPageContent($page);
            sendResponse($pageContent);
            break;
            
        case 'events_datatable':
            $auth->requireAuth();
            
            // Get DataTables parameters
            $draw = intval($_GET['draw'] ?? 1);
            $start = intval($_GET['start'] ?? 0);
            $length = intval($_GET['length'] ?? 25);
            $searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
            
            // Get ordering parameters
            $orderColumn = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 1;
            $orderDir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'asc';
            
            // Map column numbers to database fields
            $columns = ['episode_Title', 'episode_Start_Date_Time', 'episode_End_Date_Time', 'duration_minutes', 'user_name'];
            $orderBy = $columns[$orderColumn] ?? 'episode_Start_Date_Time';
            
            // Get user filter if provided
            $userIds = $_GET['user_ids'] ?? '';
            $userIdsArray = null;
            
            if ($userIds) {
                $userIdsArray = array_filter(explode(',', $userIds), 'is_numeric');
                if (empty($userIdsArray)) {
                    sendResponse([
                        'draw' => $draw,
                        'recordsTotal' => 0,
                        'recordsFiltered' => 0,
                        'data' => []
                    ]);
                    return;
                }
            }
            
            // Get paginated events data
            $result = $eventModel->getEventsForDataTable($start, $length, $searchValue, $orderBy, $orderDir, $userIdsArray);
            
            // Log for debugging
            error_log("DataTables response: " . json_encode([
                'draw' => $draw,
                'recordsTotal' => $result['total'],
                'recordsFiltered' => $result['filtered'],
                'data_count' => count($result['data']),
                'first_row' => isset($result['data'][0]) ? $result['data'][0] : null
            ]));
            
            sendResponse([
                'draw' => $draw,
                'recordsTotal' => $result['total'],
                'recordsFiltered' => $result['filtered'],
                'data' => $result['data']
            ]);
            break;
            
        case 'events_by_user':
            $auth->requireAuth();
            
            $userId = $_GET['user_id'] ?? null;
            if (!$userId || !is_numeric($userId)) {
                throw new Exception('Valid user_id parameter is required');
            }
            
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            
            $events = $eventModel->getEventsByUserId($userId, $startDate, $endDate);
            sendResponse($events);
            break;
            
        case 'events_range':
            $auth->requireAuth();
            
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            
            if (!$startDate || !$endDate) {
                throw new Exception('start_date and end_date parameters are required');
            }
            
            $userIds = $_GET['user_ids'] ?? '';
            $userIdsArray = null;
            
            if ($userIds) {
                $userIdsArray = array_filter(explode(',', $userIds), 'is_numeric');
            }
            
            $events = $eventModel->getEventsInRange($startDate, $endDate, $userIdsArray);
            sendResponse($events);
            break;
            
        case 'upcoming_events':
            $currentUser = $auth->requireAuth();
            
            $limit = (int)($_GET['limit'] ?? 10);
            $limit = max(1, min($limit, 50));
            
            $events = $eventModel->getUpcomingEvents($currentUser['id'], $limit);
            sendResponse($events);
            break;
            
        case 'search_events':
            $auth->requireAuth();
            
            $query = $_GET['query'] ?? '';
            if (empty($query)) {
                throw new Exception('Search query is required');
            }
            
            $userId = $_GET['user_id'] ?? null;
            $limit = (int)($_GET['limit'] ?? 20);
            $limit = max(1, min($limit, 100));
            
            $events = $eventModel->searchEvents($query, $userId, $limit);
            sendResponse($events);
            break;
            
        case 'event_stats':
            $currentUser = $auth->requireAuth();
            
            $userId = $_GET['user_id'] ?? $currentUser['id'];
            if ($userId != $currentUser['id']) {
                throw new Exception('You can only view your own event statistics');
            }
            
            $stats = $eventModel->getEventStats($userId);
            sendResponse($stats);
            break;
            
        case 'user_activity':
            $auth->requireAuth();
            
            $days = (int)($_GET['days'] ?? 30);
            $days = max(1, min($days, 365));
            
            $activity = $userModel->getUserActivity($days);
            sendResponse($activity);
            break;
            
        case 'calendar_updates':
            $auth->requireAuth();
            
            $lastId = (int)($_GET['last_id'] ?? 0);
            $limit = (int)($_GET['limit'] ?? 10);
            $limit = max(1, min($limit, 50));
            
            $updates = $calendarUpdate->getUpdates($lastId, $limit);
            sendResponse($updates);
            break;
            
        case 'latest_update_id':
            $auth->requireAuth();
            
            $latestId = $calendarUpdate->getLatestUpdateId();
            sendResponse(['latest_id' => $latestId]);
            break;
            
        case 'database_stats':
            $auth->requireAuth();
            
            try {
                $stats = getDatabaseStats();
                sendResponse($stats);
            } catch (Exception $e) {
                sendResponse(['error' => 'Unable to fetch database statistics']);
            }
            break;
            
        case 'test':
            $isAuthenticated = $auth->checkAuth()['authenticated'] ?? false;
            sendResponse([
                'status' => 'success',
                'message' => 'API is working',
                'timestamp' => time(),
                'authenticated' => $isAuthenticated,
                'version' => '2.2',
                'features' => [
                    'event_management' => true,
                    'user_management' => true,
                    'real_time_updates' => true,
                    'import_functionality' => 'proxied_to_worker'
                ]
            ]);
            break;
            
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
            
        // Import-related GET actions are handled by the proxy detection above
        case 'import_formats':
        case 'import_stats':
        case 'supported_import_formats':
            // These should be caught by isImportRequest() and proxied
            throw new Exception('Import request not properly routed to worker');
            break;
            
        default:
            throw new Exception('Invalid action');
    }
}

/**
 * Handle POST requests
 */
function handlePostRequest($auth, $userModel, $eventModel, $calendarUpdate) {
    // Import requests should be caught by the proxy detection
    // This function handles regular JSON API requests only
    
    $input = getJsonInput();
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'register':
            validateRequiredFields($input, ['name', 'email', 'password']);
            
            $result = $auth->register(
                trim($input['name']),
                trim($input['email']),
                $input['password']
            );
            
            sendResponse($result, $result['success'] ? 201 : 400);
            break;
            
        case 'login':
            validateRequiredFields($input, ['email', 'password']);
            
            $result = $auth->login(
                trim($input['email']),
                $input['password'],
                $input['rememberMe'] ?? false
            );
            
            sendResponse($result, $result['success'] ? 200 : 401);
            break;
            
        case 'logout':
            $result = $auth->logout();
            sendResponse($result);
            break;
            
        case 'create_user':
            $currentUser = $auth->requireAuth();
            
            if (!isset($input['userName'])) {
                throw new Exception('Missing userName field');
            }
            
            $userName = trim($input['userName']);
            if (empty($userName)) {
                throw new Exception('userName cannot be empty');
            }
            
            sendResponse([
                'id' => $currentUser['id'],
                'name' => $currentUser['name'],
                'color' => $currentUser['color'],
                'created_at' => date('Y-m-d H:i:s')
            ]);
            break;
            
        case 'create_event':
            $currentUser = $auth->requireAuth();
            
            validateRequiredFields($input, ['title', 'start']);
            
            $event = $eventModel->createEvent(
                $currentUser['id'],
                $input['title'],
                $input['start'],
                $input['end'] ?? null
            );
            
            sendResponse($event, 201);
            break;
            
        case 'search_users':
            $auth->requireAuth();
            
            validateRequiredFields($input, ['query']);
            
            $limit = (int)($input['limit'] ?? 10);
            $limit = max(1, min($limit, 50));
            
            $users = $userModel->searchUsers($input['query'], $limit);
            sendResponse($users);
            break;
            
        case 'broadcast_notification':
            $currentUser = $auth->requireAuth();
            
            validateRequiredFields($input, ['message']);
            
            $type = $input['type'] ?? 'info';
            $additionalData = $input['data'] ?? [];
            
            $success = $calendarUpdate->broadcastNotification(
                $input['message'],
                $type,
                $additionalData
            );
            
            sendResponse(['success' => $success]);
            break;
            
        case 'update_user_profile':
            $currentUser = $auth->requireAuth();
            
            $allowedFields = ['name', 'email', 'color'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updateData[$field] = $input[$field];
                }
            }
            
            if (empty($updateData)) {
                throw new Exception('No valid fields to update');
            }
            
            $updatedUser = $userModel->updateUser($currentUser['id'], $updateData);
            sendResponse($updatedUser);
            break;
            
        // SPA Support - Get dependencies
        case 'get_dependencies':
            $auth->requireAuth();
            
            if (!isset($input['dependencies']) || !is_array($input['dependencies'])) {
                throw new Exception('Missing dependencies field');
            }
            
            $dependencies = getSPADependencies($input['dependencies']);
            sendResponse($dependencies);
            break;
            
        // Import-related actions should be caught by proxy detection
        case 'import_events':
        case 'validate_import_file':
        case 'preview_import':
            throw new Exception('Import request not properly detected. Please ensure you are uploading a file.');
            break;
            
        default:
            // Default: Create event (for backward compatibility)
            $currentUser = $auth->requireAuth();
            
            validateRequiredFields($input, ['title']);
            
            if (!isset($input['start'])) {
                throw new Exception('Start date is required');
            }
            
            $event = $eventModel->createEvent(
                $currentUser['id'],
                $input['title'],
                $input['start'],
                $input['end'] ?? null
            );
            
            sendResponse($event, 201);
            break;
    }
}

/**
 * Handle PUT requests
 */
function handlePutRequest($auth, $eventModel) {
    $currentUser = $auth->requireAuth();
    $input = getJsonInput();
    
    validateRequiredFields($input, ['id', 'title', 'start']);
    
    $eventId = (int)$input['id'];
    if ($eventId <= 0) {
        throw new Exception('Invalid event ID');
    }
    
    $event = $eventModel->updateEvent($eventId, $currentUser['id'], $input);
    sendResponse($event);
}

/**
 * Handle DELETE requests
 */
function handleDeleteRequest($auth, $eventModel) {
    $currentUser = $auth->requireAuth();
    
    $eventId = $_GET['id'] ?? null;
    
    if (!$eventId || !is_numeric($eventId)) {
        throw new Exception('Missing or invalid event ID');
    }
    
    $eventId = (int)$eventId;
    if ($eventId <= 0) {
        throw new Exception('Invalid event ID');
    }
    
    $success = $eventModel->deleteEvent($eventId, $currentUser['id']);
    
    if ($success) {
        sendResponse(['success' => true, 'message' => 'Event deleted successfully']);
    } else {
        throw new Exception('Failed to delete event');
    }
}

// End output buffering and send response
ob_end_flush();
?>