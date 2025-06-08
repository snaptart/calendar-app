<?php
// REST API for calendar operations
// Save as: api.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Handle preflight requests
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
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
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGet($pdo) {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'updates':
            $lastId = $_GET['lastId'] ?? 0;
            $stmt = $pdo->prepare("SELECT * FROM calendar_updates WHERE id > ? ORDER BY id ASC LIMIT 10");
            $stmt->execute([$lastId]);
            $updates = $stmt->fetchAll();
            echo json_encode($updates);
            break;
            
        case 'users':
            $stmt = $pdo->query("SELECT * FROM users ORDER BY name");
            echo json_encode($stmt->fetchAll());
            break;
            
        case 'events':
            $userIds = $_GET['user_ids'] ?? '';
            if ($userIds) {
                $userIds = explode(',', $userIds);
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
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePost($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['userName']) || !isset($input['title'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }
    
    // Get or create user
    $user = getOrCreateUser($pdo, $input['userName']);
    
    // Create event
    $stmt = $pdo->prepare("
        INSERT INTO events (user_id, title, start_datetime, end_datetime) 
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $user['id'],
        $input['title'],
        $input['start'],
        $input['end'] ?? $input['start']
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

function handlePut($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing event ID']);
        return;
    }
    
    // Update event
    $stmt = $pdo->prepare("
        UPDATE events 
        SET title = ?, start_datetime = ?, end_datetime = ? 
        WHERE id = ?
    ");
    
    $stmt->execute([
        $input['title'],
        $input['start'],
        $input['end'] ?? $input['start'],
        $input['id']
    ]);
    
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
    
    if (!$eventId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing event ID']);
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
    
    echo json_encode(['success' => true]);
}
?>