<?php
// Performance monitoring tool for event-driven calendar system
// Access this file to see real-time performance metrics

require_once 'config.php';

header('Content-Type: application/json');

try {
    $pdo = getDatabase();
    
    // Get event statistics
    $stmt = $pdo->query('SELECT COUNT(*) as total_events FROM events');
    $totalEvents = $stmt->fetchColumn();
    
    // Get notification statistics
    $stmt = $pdo->query('SELECT COUNT(*) as total_notifications FROM event_notifications');
    $totalNotifications = $stmt->fetchColumn();
    
    // Get recent activity (last 5 minutes)
    $stmt = $pdo->query('
        SELECT COUNT(*) as recent_events 
        FROM events 
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
           OR updated_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ');
    $recentEvents = $stmt->fetchColumn();
    
    // Get user statistics
    $stmt = $pdo->query('
        SELECT COUNT(DISTINCT user_name) as total_users,
               AVG(event_count) as avg_events_per_user
        FROM (
            SELECT user_name, COUNT(*) as event_count 
            FROM events 
            GROUP BY user_name
        ) user_stats
    ');
    $userStats = $stmt->fetch();
    
    // Get oldest and newest notification IDs
    $stmt = $pdo->query('SELECT MIN(id) as min_id, MAX(id) as max_id FROM event_notifications');
    $notificationRange = $stmt->fetch();
    
    // Calculate memory table size estimate
    // Each row in MEMORY table typically uses about 50-100 bytes
    $estimatedMemoryUsage = $totalNotifications * 75; // bytes
    
    // Get MySQL process list to see active SSE connections
    $stmt = $pdo->query("SHOW PROCESSLIST");
    $processes = $stmt->fetchAll();
    $sseConnections = 0;
    
    foreach ($processes as $process) {
        if (strpos($process['Info'], 'event_notifications') !== false) {
            $sseConnections++;
        }
    }
    
    // Get recent activity by action type
    $stmt = $pdo->query('
        SELECT action, COUNT(*) as count 
        FROM event_notifications 
        WHERE timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        GROUP BY action
    ');
    $recentActions = $stmt->fetchAll();
    
    // Performance metrics
    $metrics = [
        'timestamp' => date('c'),
        'database' => [
            'total_events' => (int)$totalEvents,
            'recent_events_5min' => (int)$recentEvents,
            'events_per_minute' => round($recentEvents / 5, 2)
        ],
        'users' => [
            'total_users' => (int)$userStats['total_users'],
            'avg_events_per_user' => round($userStats['avg_events_per_user'], 2)
        ],
        'notifications' => [
            'total_notifications' => (int)$totalNotifications,
            'notification_range' => $notificationRange,
            'recent_actions' => $recentActions,
            'estimated_memory_usage' => [
                'bytes' => $estimatedMemoryUsage,
                'kb' => round($estimatedMemoryUsage / 1024, 2),
                'mb' => round($estimatedMemoryUsage / (1024 * 1024), 3)
            ]
        ],
        'connections' => [
            'active_sse_connections' => $sseConnections,
            'total_processes' => count($processes)
        ],
        'performance' => [
            'queries_per_second_estimate' => $sseConnections, // One notification check per second per connection
            'efficiency_improvement' => [
                'old_system_queries_per_second' => $sseConnections, // Was 1 query per connection per second
                'new_system_queries_per_second' => round($recentEvents / 300, 2), // Only when events change
                'reduction_percentage' => $recentEvents > 0 ? 
                    round((1 - ($recentEvents / 300) / max($sseConnections, 1)) * 100, 1) : 100
            ]
        ],
        'system_info' => [
            'php_memory_usage' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'formatted' => [
                    'current' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                    'peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB'
                ]
            ]
        ]
    ];
    
    echo json_encode($metrics, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to get performance metrics',
        'message' => $e->getMessage()
    ]);
}
?>