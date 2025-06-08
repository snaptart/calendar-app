<?php
/**
 * REST API for calendar operations
 * Location: backend/api.php
 * 
 * Handles all CRUD operations for the collaborative calendar application.
 * Supports GET, POST, PUT, and DELETE methods for events and users.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'database/config.php';

$method = $_SERVER['REQUEST_METHOD'];

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

handleRequest(function() use ($pdo, $method) {
    switch ($method) {
        case 'GET':
            handleGet($pdo);
            break;
        case 'POST':
            handlePost($pdo);
            break;
        case 'PUT':
            handlePut($pdo);
            break;
        case 'DELETE':
            handleDelete($pdo);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
});

function handleGet($pdo) {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'users':
            $stmt = $pdo->query("SELECT * FROM users ORDER BY name");
            echo json_encode($stmt->fetchAll());
            break;
            
        case 'events':
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
                'timestamp' => time()
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePost($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        return;
    }
    
    // Check if this is a user creation request
    if (isset($input['action']) && $input['action'] === 'create_user') {
        handleCreateUser($pdo, $input);
        return;
    }
    
    // Handle event creation
    if (!isset($input['userName']) || !isset($input['title'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: userName and title are required']);
        return;
    }
    
    // Validate input
    $userName = trim($input['userName']);
    $title = trim($input['title']);
    
    if (empty($userName) || empty($title)) {
        http_response_code(400);
        echo json_encode(['error' => 'userName and title cannot be empty']);
        return;
    }
    
    // Get or create user
    $user = getOrCreateUser($pdo, $userName);
    
    // Validate dates
    $startDate = $input['start'];
    $endDate = $input['end'] ?? $input['start'];
    
    if (!$startDate) {
        http_response_code(400);
        echo json_encode(['error' => 'Start date is required']);
        return;
    }
    
    // Create event
    $stmt = $pdo->prepare("
        INSERT INTO events (user_id, title, start_datetime, end_datetime) 
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $user['id'],
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
}

function handleCreateUser($pdo, $input) {
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
    
    try {
        // Check if user already exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE name = ?");
        $stmt->execute([$userName]);
        $existingUser = $stmt->fetch();
        
        if ($existingUser) {
            // User already exists, return existing user
            echo json_encode($existingUser);
            return;
        }
        
        // Create new user with random color
        $colors = ['#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#34495e', '#e67e22', '#3498db'];
        $color = $colors[array_rand($colors)];
        
        $stmt = $pdo->prepare("INSERT INTO users (name, color) VALUES (?, ?)");
        $stmt->execute([$userName, $color]);
        
        $newUser = [
            'id' => $pdo->lastInsertId(),
            'name' => $userName,
            'color' => $color,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Broadcast that a new user was created
        broadcastUpdate($pdo, 'user_created', [
            'user' => $newUser,
            'message' => "New user '{$userName}' joined the calendar"
        ]);
        
        echo json_encode($newUser);
        
    } catch (PDOException $e) {
        error_log("Error creating user: " . $e->getMessage());
        
        // Check if it's a duplicate key error
        if ($e->getCode() == 23000) {
            // User was created by another request, fetch and return
            $stmt = $pdo->prepare("SELECT * FROM users WHERE name = ?");
            $stmt->execute([$userName]);
            $user = $stmt->fetch();
            
            if ($user) {
                echo json_encode($user);
                return;
            }
        }
        
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create user']);
    }
}

function handlePut($pdo) {
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

function handleDelete($pdo) {
    $eventId = $_GET['id'] ?? null;
    
    if (!$eventId || !is_numeric($eventId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid event ID']);
        return;
    }
    
    // Get event before deletion for broadcast
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();
    
    if (!$event) {
        http_response_code(404);
        echo json_encode(['error' => 'Event not found']);
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