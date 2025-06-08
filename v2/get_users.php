<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

try {
    $pdo = getDatabase();
    
    // Get all unique users who have created events
    $stmt = $pdo->prepare('
        SELECT user_name, color, COUNT(*) as event_count,
               MAX(updated_at) as last_activity
        FROM events 
        GROUP BY user_name, color 
        ORDER BY last_activity DESC
    ');
    $stmt->execute();
    
    $users = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_users.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
    
} catch (Exception $e) {
    error_log("Error in get_users.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while getting users']);
}
?>