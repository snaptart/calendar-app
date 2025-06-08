<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

// Only allow POST, PUT, DELETE requests
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'])) {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $pdo = getDatabase();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Create new event
        $username = isset($_POST['username']) ? sanitizeInput($_POST['username']) : '';
        $title = isset($_POST['title']) ? sanitizeInput($_POST['title']) : '';
        $start = isset($_POST['start']) ? $_POST['start'] : '';
        $end = isset($_POST['end']) ? $_POST['end'] : '';
        $allDay = isset($_POST['all_day']) ? (bool)$_POST['all_day'] : false;
        
        // Validate username
        if (!validateUsername($username)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid username. Use only letters, numbers, spaces, hyphens, underscores, and dots. Max 50 characters.']);
            exit;
        }
        
        // Validate event data
        $validationError = validateEvent($title, $start, $end);
        if ($validationError) {
            http_response_code(400);
            echo json_encode(['error' => $validationError]);
            exit;
        }
        
        // Get user color
        $color = getUserColor($username);
        
        // Insert event
        $stmt = $pdo->prepare('
            INSERT INTO events (user_name, title, start_datetime, end_datetime, all_day, color) 
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $result = $stmt->execute([$username, $title, $start, $end, $allDay, $color]);
        
        if ($result) {
            $eventId = $pdo->lastInsertId();
            
            // Add notification for real-time updates
            addEventNotification($pdo, $eventId, 'create', $username);
            
            echo json_encode([
                'success' => true,
                'event_id' => $eventId,
                'message' => 'Event created successfully'
            ]);
        } else {
            throw new Exception('Failed to create event');
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Update existing event
        parse_str(file_get_contents('php://input'), $_PUT);
        
        $eventId = isset($_PUT['id']) ? (int)$_PUT['id'] : 0;
        $username = isset($_PUT['username']) ? sanitizeInput($_PUT['username']) : '';
        $title = isset($_PUT['title']) ? sanitizeInput($_PUT['title']) : '';
        $start = isset($_PUT['start']) ? $_PUT['start'] : '';
        $end = isset($_PUT['end']) ? $_PUT['end'] : '';
        $allDay = isset($_PUT['all_day']) ? (bool)$_PUT['all_day'] : false;
        
        if (!$eventId) {
            http_response_code(400);
            echo json_encode(['error' => 'Event ID is required']);
            exit;
        }
        
        // Validate username
        if (!validateUsername($username)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid username']);
            exit;
        }
        
        // Validate event data
        $validationError = validateEvent($title, $start, $end);
        if ($validationError) {
            http_response_code(400);
            echo json_encode(['error' => $validationError]);
            exit;
        }
        
        // Check if event exists and belongs to user
        $stmt = $pdo->prepare('SELECT user_name FROM events WHERE id = ?');
        $stmt->execute([$eventId]);
        $existingEvent = $stmt->fetch();
        
        if (!$existingEvent) {
            http_response_code(404);
            echo json_encode(['error' => 'Event not found']);
            exit;
        }
        
        if ($existingEvent['user_name'] !== $username) {
            http_response_code(403);
            echo json_encode(['error' => 'You can only edit your own events']);
            exit;
        }
        
        // Update event
        $stmt = $pdo->prepare('
            UPDATE events 
            SET title = ?, start_datetime = ?, end_datetime = ?, all_day = ?, updated_at = NOW()
            WHERE id = ?
        ');
        $result = $stmt->execute([$title, $start, $end, $allDay, $eventId]);
        
        if ($result) {
            // Add notification for real-time updates
            addEventNotification($pdo, $eventId, 'update', $username);
            
            echo json_encode([
                'success' => true,
                'message' => 'Event updated successfully'
            ]);
        } else {
            throw new Exception('Failed to update event');
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Delete event
        parse_str(file_get_contents('php://input'), $_DELETE);
        
        $eventId = isset($_DELETE['id']) ? (int)$_DELETE['id'] : 0;
        $username = isset($_DELETE['username']) ? sanitizeInput($_DELETE['username']) : '';
        
        if (!$eventId) {
            http_response_code(400);
            echo json_encode(['error' => 'Event ID is required']);
            exit;
        }
        
        // Validate username
        if (!validateUsername($username)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid username']);
            exit;
        }
        
        // Check if event exists and belongs to user
        $stmt = $pdo->prepare('SELECT user_name FROM events WHERE id = ?');
        $stmt->execute([$eventId]);
        $existingEvent = $stmt->fetch();
        
        if (!$existingEvent) {
            http_response_code(404);
            echo json_encode(['error' => 'Event not found']);
            exit;
        }
        
        if ($existingEvent['user_name'] !== $username) {
            http_response_code(403);
            echo json_encode(['error' => 'You can only delete your own events']);
            exit;
        }
        
        // Delete event
        $stmt = $pdo->prepare('DELETE FROM events WHERE id = ?');
        $result = $stmt->execute([$eventId]);
        
        if ($result) {
            // Add notification for real-time updates
            addEventNotification($pdo, $eventId, 'delete', $username);
            
            echo json_encode([
                'success' => true,
                'message' => 'Event deleted successfully'
            ]);
        } else {
            throw new Exception('Failed to delete event');
        }
    }
    
} catch (PDOException $e) {
    error_log("Database error in manage_event.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
    
} catch (Exception $e) {
    error_log("Error in manage_event.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while processing the event']);
}
?>