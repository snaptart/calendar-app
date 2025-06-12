<?php
/**
 * Server-Sent Events for real-time updates
 * Location: backend/workers/sse.php
 * 
 * This worker handles real-time communication between the server and clients
 * using Server-Sent Events (SSE). It monitors the calendar_updates table
 * and broadcasts changes to all connected clients.
 */

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Cache-Control');

require_once '../database/config.php';

// Keep track of last sent update ID
$lastId = isset($_GET['lastEventId']) ? (int)$_GET['lastEventId'] : 0;

/**
 * Send SSE message to client
 * 
 * @param int $id Event ID
 * @param string $type Event type
 * @param array $data Event data
 */
function sendSSE($id, $type, $data) {
    echo "id: $id\n";
    echo "event: $type\n";
    echo "data: " . json_encode($data) . "\n\n";
    ob_flush();
    flush();
}

/**
 * Log SSE events for debugging
 * 
 * @param string $message Log message
 */
function logSSEEvent($message) {
    error_log("SSE: " . $message);
}

// Send initial heartbeat
sendSSE(0, 'heartbeat', ['timestamp' => time(), 'message' => 'SSE connection established']);
logSSEEvent("Client connected with lastEventId: $lastId");

// Main event loop configuration
$maxExecutionTime = 300; // 5 minutes maximum execution time
$startTime = time();
$checkInterval = 1; // Check every 1 second
$heartbeatInterval = 10; // Send heartbeat every 10 seconds
$lastHeartbeat = time();

// Main loop for monitoring updates
while (time() - $startTime < $maxExecutionTime) {
    try {
        // Check for new updates in the database
        $stmt = $pdo->prepare("SELECT * FROM event_updates WHERE id > ? ORDER BY id ASC LIMIT 10");
        $stmt->execute([$lastId]);
        $updates = $stmt->fetchAll();
        
        // Process and send each update
        foreach ($updates as $update) {
            sendSSE(
                $update['id'], 
                $update['event_type'], 
                json_decode($update['event_data'], true)
            );
            $lastId = $update['id'];
            
            logSSEEvent("Sent update ID {$update['id']} of type {$update['event_type']}");
        }
        
        // Clean up old updates periodically to prevent table bloat
        if (rand(1, 100) === 1) { // 1% chance to clean up on each iteration
            try {
                // Use the CalendarUpdate model's cleanup method instead of raw SQL
                global $calendarUpdate;
                $deletedCount = $calendarUpdate->cleanupOldUpdates(1, 100); // Keep only last 100 records, delete older than 1 hour
                if ($deletedCount > 0) {
                    logSSEEvent("Cleaned up $deletedCount old update records");
                }
            } catch (Exception $cleanupError) {
                logSSEEvent("Cleanup error: " . $cleanupError->getMessage());
            }
        }
        
        // Send periodic heartbeat to keep connection alive
        $currentTime = time();
        if ($currentTime - $lastHeartbeat >= $heartbeatInterval) {
            sendSSE($lastId, 'heartbeat', [
                'timestamp' => $currentTime,
                'message' => 'Connection alive',
                'lastEventId' => $lastId
            ]);
            $lastHeartbeat = $currentTime;
        }
        
        // Sleep before next check
        sleep($checkInterval);
        
        // Check if client is still connected
        if (connection_aborted()) {
            logSSEEvent("Client disconnected");
            break;
        }
        
    } catch (Exception $e) {
        logSSEEvent("Error in main loop: " . $e->getMessage());
        sendSSE($lastId, 'error', [
            'message' => 'Server error occurred',
            'timestamp' => time()
        ]);
        break;
    }
}

// Send final message when connection times out or ends
sendSSE($lastId, 'timeout', [
    'message' => 'Connection timeout - please reconnect',
    'timestamp' => time(),
    'lastEventId' => $lastId
]);

logSSEEvent("SSE connection ended. Duration: " . (time() - $startTime) . " seconds");

// Ensure all output is sent before closing
ob_end_flush();
flush();
?>