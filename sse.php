<?php
// Server-Sent Events for real-time updates
// Save as: sse.php

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Cache-Control');

require_once 'config.php';

// Keep track of last sent update ID
$lastId = isset($_GET['lastEventId']) ? (int)$_GET['lastEventId'] : 0;

// Function to send SSE message
function sendSSE($id, $type, $data) {
    echo "id: $id\n";
    echo "event: $type\n";
    echo "data: " . json_encode($data) . "\n\n";
    ob_flush();
    flush();
}

// Send initial heartbeat
sendSSE(0, 'heartbeat', ['timestamp' => time()]);

// Main loop
$maxExecutionTime = 30; // 30 seconds
$startTime = time();
$checkInterval = 1; // Check every 1 second

while (time() - $startTime < $maxExecutionTime) {
    try {
        // Check for new updates
        $stmt = $pdo->prepare("SELECT * FROM calendar_updates WHERE id > ? ORDER BY id ASC LIMIT 10");
        $stmt->execute([$lastId]);
        $updates = $stmt->fetchAll();
        
        foreach ($updates as $update) {
            sendSSE(
                $update['id'], 
                $update['event_type'], 
                json_decode($update['event_data'], true)
            );
            $lastId = $update['id'];
        }
        
        // Clean up old updates (keep only last 100)
        $pdo->exec("DELETE FROM calendar_updates WHERE id < (SELECT id FROM (SELECT id FROM calendar_updates ORDER BY id DESC LIMIT 1 OFFSET 100) AS t)");
        
        // Send periodic heartbeat
        if (time() % 10 == 0) {
            sendSSE($lastId, 'heartbeat', ['timestamp' => time()]);
        }
        
        sleep($checkInterval);
        
    } catch (Exception $e) {
        sendSSE($lastId, 'error', ['message' => $e->getMessage()]);
        break;
    }
}

// Connection timeout
sendSSE($lastId, 'timeout', ['message' => 'Connection timeout']);
?>