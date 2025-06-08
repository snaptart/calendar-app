<?php
// Set headers for Server-Sent Events
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Cache-Control');

require_once 'config.php';

// Disable output buffering
if (ob_get_level()) {
    ob_end_clean();
}

// Set time limit to 0 (no time limit)
set_time_limit(0);

// Get the last notification ID from the client
$lastNotificationId = isset($_GET['lastId']) ? (int)$_GET['lastId'] : 0;

// Function to send SSE data
function sendSSEData($data, $event = null) {
    if ($event) {
        echo "event: $event\n";
    }
    echo "data: " . json_encode($data) . "\n\n";
    flush();
}

// Function to get new notifications
function getNewNotifications($pdo, $lastId) {
    $stmt = $pdo->prepare('
        SELECT id, event_id, action, user_name, timestamp 
        FROM event_notifications 
        WHERE id > ? 
        ORDER BY id ASC 
        LIMIT 50
    ');
    $stmt->execute([$lastId]);
    return $stmt->fetchAll();
}

// Function to get event details
function getEventDetails($pdo, $eventId) {
    $stmt = $pdo->prepare('
        SELECT id, user_name, title, start_datetime, end_datetime, all_day, color 
        FROM events 
        WHERE id = ?
    ');
    $stmt->execute([$eventId]);
    return $stmt->fetch();
}

// Function to get all events (for initial load)
function getAllEvents($pdo) {
    $stmt = $pdo->prepare('
        SELECT id, user_name, title, start_datetime, end_datetime, all_day, color 
        FROM events 
        ORDER BY start_datetime ASC
    ');
    $stmt->execute();
    return $stmt->fetchAll();
}

try {
    $pdo = getDatabase();
    $heartbeatCounter = 0;
    
    // Send initial events on first connection
    if ($lastNotificationId === 0) {
        $allEvents = getAllEvents($pdo);
        sendSSEData([
            'type' => 'initial_load',
            'events' => $allEvents
        ]);
    }
    
    // Main SSE loop
    while (true) {
        // Check if client disconnected
        if (connection_aborted()) {
            break;
        }
        
        // Get new notifications
        $notifications = getNewNotifications($pdo, $lastNotificationId);
        
        if (!empty($notifications)) {
            foreach ($notifications as $notification) {
                $data = [
                    'type' => 'event_change',
                    'notification_id' => (int)$notification['id'],
                    'action' => $notification['action'],
                    'user_name' => $notification['user_name'],
                    'timestamp' => $notification['timestamp']
                ];
                
                // Get event details for create/update actions
                if ($notification['action'] === 'create' || $notification['action'] === 'update') {
                    $event = getEventDetails($pdo, $notification['event_id']);
                    if ($event) {
                        $data['event'] = $event;
                    }
                } else if ($notification['action'] === 'delete') {
                    $data['event_id'] = (int)$notification['event_id'];
                }
                
                sendSSEData($data);
                
                // Update last notification ID
                $lastNotificationId = max($lastNotificationId, (int)$notification['id']);
            }
        }
        
        // Send heartbeat every 30 seconds to keep connection alive
        $heartbeatCounter++;
        if ($heartbeatCounter >= 30) {
            sendSSEData([
                'type' => 'heartbeat',
                'timestamp' => date('c')
            ]);
            $heartbeatCounter = 0;
        }
        
        // Sleep for 1 second before checking for new notifications
        sleep(1);
    }
    
} catch (PDOException $e) {
    error_log("Database error in get_events.php: " . $e->getMessage());
    sendSSEData([
        'type' => 'error',
        'message' => 'Database connection error'
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_events.php: " . $e->getMessage());
    sendSSEData([
        'type' => 'error',
        'message' => 'Server error occurred'
    ]);
}
?>