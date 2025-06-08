<?php
/**
 * Updated REST API for calendar operations with authentication
 * Location: backend/api.php
 * 
 * Handles all CRUD operations for the collaborative calendar application
 * with user authentication and session management.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

require_once 'database/config.php';
require_once 'auth/Auth.php';

$method = $_SERVER['REQUEST_METHOD'];

// Initialize authentication
$auth = new Auth($pdo);

// Handle preflight requests
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Error handling wrapper
function handleRequest($callback) {
    try {
        $callback();
    } catch (Exception $e) {
        error_log("API Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    }
}

handleRequest(function() use ($pdo, $method, $auth) {
    switch ($method) {
        case 'GET':
            handleGet($pdo, $auth);
            break;
        case 'POST':
            handlePost($pdo, $auth);
            break;
        case 'PUT':
            handlePut($pdo, $auth);
            break;
        case 'DELETE':
            handleDelete($pdo, $auth);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
});

function handleGet($pdo, $auth) {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'check_auth':
            // Check authentication status
            $authResult = $auth->checkAuth();
            echo json_encode($authResult);
            break;
            
        case 'users':
            // Get all users (requires authentication)
            $currentUser = $auth->requireAuth();
            
            $stmt = $pdo->query("SELECT id, name, color, created_at FROM users ORDER BY name");
            echo json_encode($stmt->fetchAll());
            break;

		case 'users_with_stats':
			// Get all users with event statistics (requires authentication)
			$currentUser = $auth->requireAuth();

			try {
				// Get all users
				$stmt = $pdo->query("
            SELECT
                u.id,
                u.name,
                u.email,
                u.color,
                u.created_at,
                u.last_login,
                COUNT(e.id) as event_count
            FROM users u
            LEFT JOIN events e ON u.id = e.user_id
            GROUP BY u.id, u.name, u.email, u.color, u.created_at, u.last_login
            ORDER BY u.name ASC
        ");

				$users = $stmt->fetchAll();

				// Format the data for better frontend consumption
				$formattedUsers = array_map(function($user) {
					// Determine user status based on last login
					$status = 'new';
					if ($user['last_login'] && $user['last_login'] !== '0000-00-00 00:00:00') {
						$lastLogin = new DateTime($user['last_login']);
						$now = new DateTime();
						$daysDiff = $now->diff($lastLogin)->days;

						if ($daysDiff <= 1) {
							$status = 'active';
						} else {
							$status = 'inactive';
						}
					}

					return [
						'id' => (int)$user['id'],
						'name' => $user['name'],
						'email' => $user['email'],
						'color' => $user['color'],
						'created_at' => $user['created_at'],
						'last_login' => $user['last_login'],
						'event_count' => (int)$user['event_count'],
						'status' => $status
					];
				}, $users);

				echo json_encode($formattedUsers);
			} catch (PDOException $e) {
				error_log("Error fetching users with stats: " . $e->getMessage());
				http_response_code(500);
				echo json_encode(['error' => 'Failed to fetch users data']);
			}
			break;
            
        case 'events':
            // Get events (requires authentication)
            $currentUser = $auth->requireAuth();
            
            $userIds = $_GET['user_ids'] ?? '';
            if ($userIds) {
                $userIds = array_filter(explode(',', $userIds), 'is_numeric');
                if (empty($userIds)) {
                    echo json_encode([]);
                    return;
                }
                
                $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
                $stmt = $pdo->prepare("
                    SELECT e.*, u.name as user_name, u.color as user_color 
                    FROM events e 
                    JOIN users u ON e.user_id = u.id 
                    WHERE e.user_id IN ($placeholders)
                    ORDER BY e.start_datetime
                ");
                $stmt->execute($userIds);
            } else {
                $stmt = $pdo->query("
                    SELECT e.*, u.name as user_name, u.color as user_color 
                    FROM events e 
                    JOIN users u ON e.user_id = u.id 
                    ORDER BY e.start_datetime
                ");
            }
            
            $events = $stmt->fetchAll();
            
            // Format for FullCalendar
            $formattedEvents = array_map(function($event) {
                return [
                    'id' => $event['id'],
                    'title' => $event['title'],
                    'start' => $event['start_datetime'],
                    'end' => $event['end_datetime'],
                    'backgroundColor' => $event['user_color'],
                    'borderColor' => $event['user_color'],
                    'extendedProps' => [
                        'userId' => $event['user_id'],
                        'userName' => $event['user_name']
                    ]
                ];
            }, $events);
            
            echo json_encode($formattedEvents);
            break;
            
        case 'test':
            // Test endpoint to verify API is working
            echo json_encode([
                'status' => 'success',
                'message' => 'API is working',
                'timestamp' => time(),
                'authenticated' => $auth->checkAuth()['authenticated'] ?? false
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePost($pdo, $auth) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        return;
    }
    
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'register':
            // User registration
            if (!isset($input['name'], $input['email'], $input['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields: name, email, password']);
                return;
            }
            
            $result = $auth->register(
                trim($input['name']),
                trim($input['email']),
                $input['password']
            );
            
            echo json_encode($result);
            break;
            
        case 'login':
            // User login
            if (!isset($input['email'], $input['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields: email, password']);
                return;
            }
            
            $result = $auth->login(
                trim($input['email']),
                $input['password'],
                $input['rememberMe'] ?? false
            );
            
            echo json_encode($result);
            break;
            
        case 'logout':
            // User logout
            $result = $auth->logout();
            echo json_encode($result);
            break;
            
        case 'create_user':
            // Legacy user creation (now redirects to registration)
            // This maintains backward compatibility with existing frontend code
            if (!isset($input['userName'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing userName field']);
                return;
            }
            
            $userName = trim($input['userName']);
            
            if (empty($userName)) {
                http_response_code(400);
                echo json_encode(['error' => 'userName cannot be empty']);
                return;
            }
            
            // For backward compatibility, we'll look up user by name
            // In the new system, users should be authenticated
            $currentUser = $auth->getCurrentUser();
            if (!$currentUser) {
                http_response_code(401);
                echo json_encode(['error' => 'Authentication required']);
                return;
            }
            
            echo json_encode([
                'id' => $currentUser['id'],
                'name' => $currentUser['name'],
                'color' => $currentUser['color'],
                'created_at' => date('Y-m-d H:i:s')
            ]);
            break;
            
        default:
            // Handle event creation (requires authentication)
            $currentUser = $auth->requireAuth();
            
            if (!isset($input['title'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required field: title']);
                return;
            }
            
            // Validate input
            $title = trim($input['title']);
            
            if (empty($title)) {
                http_response_code(400);
                echo json_encode(['error' => 'Title cannot be empty']);
                return;
            }
            
            // Validate dates
            $startDate = $input['start'];
            $endDate = $input['end'] ?? $input['start'];
            
            if (!$startDate) {
                http_response_code(400);
                echo json_encode(['error' => 'Start date is required']);
                return;
            }
            
            // Create event using authenticated user
            $stmt = $pdo->prepare("
                INSERT INTO events (user_id, title, start_datetime, end_datetime) 
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $currentUser['id'],
                $title,
                $startDate,
                $endDate
            ]);
            
            $eventId = $pdo->lastInsertId();
            
            // Get the created event with user info
            $stmt = $pdo->prepare("
                SELECT e.*, u.name as user_name, u.color as user_color 
                FROM events e 
                JOIN users u ON e.user_id = u.id 
                WHERE e.id = ?
            ");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch();
            
            // Format for FullCalendar
            $formattedEvent = [
                'id' => $event['id'],
                'title' => $event['title'],
                'start' => $event['start_datetime'],
                'end' => $event['end_datetime'],
                'backgroundColor' => $event['user_color'],
                'borderColor' => $event['user_color'],
                'extendedProps' => [
                    'userId' => $event['user_id'],
                    'userName' => $event['user_name']
                ]
            ];
            
            // Broadcast update
            broadcastUpdate($pdo, 'create', $formattedEvent);
            
            echo json_encode($formattedEvent);
            break;
    }
}

function handlePut($pdo, $auth) {
    // Require authentication for event updates
    $currentUser = $auth->requireAuth();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing event ID']);
        return;
    }
    
    // Validate required fields
    if (!isset($input['title']) || !isset($input['start'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: title and start are required']);
        return;
    }
    
    // Check if user owns this event
    $stmt = $pdo->prepare("SELECT user_id FROM events WHERE id = ?");
    $stmt->execute([$input['id']]);
    $event = $stmt->fetch();
    
    if (!$event) {
        http_response_code(404);
        echo json_encode(['error' => 'Event not found']);
        return;
    }
    
    if ($event['user_id'] != $currentUser['id']) {
        http_response_code(403);
        echo json_encode(['error' => 'You can only edit your own events']);
        return;
    }
    
    // Update event
    $stmt = $pdo->prepare("
        UPDATE events 
        SET title = ?, start_datetime = ?, end_datetime = ? 
        WHERE id = ?
    ");
    
    $stmt->execute([
        trim($input['title']),
        $input['start'],
        $input['end'] ?? $input['start'],
        $input['id']
    ]);
    
    // Check if event was actually updated
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Event not found or no changes made']);
        return;
    }
    
    // Get updated event with user info
    $stmt = $pdo->prepare("
        SELECT e.*, u.name as user_name, u.color as user_color 
        FROM events e 
        JOIN users u ON e.user_id = u.id 
        WHERE e.id = ?
    ");
    $stmt->execute([$input['id']]);
    $event = $stmt->fetch();
    
    if ($event) {
        // Format for FullCalendar
        $formattedEvent = [
            'id' => $event['id'],
            'title' => $event['title'],
            'start' => $event['start_datetime'],
            'end' => $event['end_datetime'],
            'backgroundColor' => $event['user_color'],
            'borderColor' => $event['user_color'],
            'extendedProps' => [
                'userId' => $event['user_id'],
                'userName' => $event['user_name']
            ]
        ];
        
        // Broadcast update
        broadcastUpdate($pdo, 'update', $formattedEvent);
        
        echo json_encode($formattedEvent);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Event not found']);
    }
}

function handleDelete($pdo, $auth) {
    // Require authentication for event deletion
    $currentUser = $auth->requireAuth();
    
    $eventId = $_GET['id'] ?? null;
    
    if (!$eventId || !is_numeric($eventId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid event ID']);
        return;
    }
    
    // Get event and check ownership
    $stmt = $pdo->prepare("SELECT user_id FROM events WHERE id = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();
    
    if (!$event) {
        http_response_code(404);
        echo json_encode(['error' => 'Event not found']);
        return;
    }
    
    if ($event['user_id'] != $currentUser['id']) {
        http_response_code(403);
        echo json_encode(['error' => 'You can only delete your own events']);
        return;
    }
    
    // Delete event
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$eventId]);
    
    // Broadcast update
    broadcastUpdate($pdo, 'delete', ['id' => $eventId]);
    
    echo json_encode(['success' => true, 'message' => 'Event deleted successfully']);
}
?>