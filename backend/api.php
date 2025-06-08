<?php
/**
 * Refactored REST API Controller for Collaborative Calendar
 * Location: backend/api.php
 * 
 * Pure REST controller that delegates business logic to model classes.
 * Handles HTTP routing, request/response formatting, and input validation.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

require_once 'database/config.php';
require_once 'auth/Auth.php';
require_once 'models/User.php';
require_once 'models/Event.php';
require_once 'models/CalendarUpdate.php';

$method = $_SERVER['REQUEST_METHOD'];

// Initialize models
$calendarUpdate = new CalendarUpdate($pdo);
$userModel = new User($pdo, $calendarUpdate);
$eventModel = new Event($pdo, $calendarUpdate);
$auth = new Auth($pdo);

// Initialize calendar updates system
$calendarUpdate->initialize();

// Handle preflight requests
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Error handling wrapper
 */
function handleRequest($callback) {
    try {
        $callback();
    } catch (Exception $e) {
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

// Route the request
handleRequest(function() use ($pdo, $method, $auth, $userModel, $eventModel, $calendarUpdate) {
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
            $limit = max(1, min($limit, 50)); // Limit between 1 and 50
            
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
            $limit = max(1, min($limit, 100)); // Limit between 1 and 100
            
            $events = $eventModel->searchEvents($query, $userId, $limit);
            sendResponse($events);
            break;
            
        case 'event_stats':
            $currentUser = $auth->requireAuth();
            
            $userId = $_GET['user_id'] ?? $currentUser['id'];
            if ($userId != $currentUser['id']) {
                // Users can only see their own detailed stats
                throw new Exception('You can only view your own event statistics');
            }
            
            $stats = $eventModel->getEventStats($userId);
            sendResponse($stats);
            break;
            
        case 'user_stats':
            $currentUser = $auth->requireAuth();
            
            $userId = $_GET['user_id'] ?? $currentUser['id'];
            if ($userId != $currentUser['id']) {
                throw new Exception('You can only view your own statistics');
            }
            
            $stats = $userModel->getUserStats($userId);
            sendResponse($stats);
            break;
            
        case 'calendar_updates':
            $auth->requireAuth();
            
            $lastId = (int)($_GET['last_id'] ?? 0);
            $limit = (int)($_GET['limit'] ?? 10);
            $limit = max(1, min($limit, 50)); // Limit between 1 and 50
            
            $updates = $calendarUpdate->getUpdates($lastId, $limit);
            sendResponse($updates);
            break;
            
        case 'latest_update_id':
            $auth->requireAuth();
            
            $latestId = $calendarUpdate->getLatestUpdateId();
            sendResponse(['latest_id' => $latestId]);
            break;
            
        case 'test':
            $isAuthenticated = $auth->checkAuth()['authenticated'] ?? false;
            sendResponse([
                'status' => 'success',
                'message' => 'API is working',
                'timestamp' => time(),
                'authenticated' => $isAuthenticated
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
}

/**
 * Handle POST requests
 */
function handlePostRequest($auth, $userModel, $eventModel, $calendarUpdate) {
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
            // Legacy support - now requires authentication
            $currentUser = $auth->requireAuth();
            
            if (!isset($input['userName'])) {
                throw new Exception('Missing userName field');
            }
            
            $userName = trim($input['userName']);
            if (empty($userName)) {
                throw new Exception('userName cannot be empty');
            }
            
            // Return current user info for backward compatibility
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
            $limit = max(1, min($limit, 50)); // Limit between 1 and 50
            
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
?>