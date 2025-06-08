<?php
// Database configuration
// Save as: config.php

$host = 'localhost';
$dbname = 'collaborative_calendar';
$username = 'root'; // Change to your MySQL username
$password = '';     // Change to your MySQL password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5
    ]);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die(json_encode(['error' => 'Database connection failed. Please check your configuration.']));
}

// Helper function to get or create user
function getOrCreateUser($pdo, $name) {
    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE name = ?");
        $stmt->execute([$name]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // Create new user with random color
            $colors = ['#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#34495e', '#e67e22', '#3498db'];
            $color = $colors[array_rand($colors)];
            
            $stmt = $pdo->prepare("INSERT INTO users (name, color) VALUES (?, ?)");
            $stmt->execute([$name, $color]);
            
            $user = [
                'id' => $pdo->lastInsertId(),
                'name' => $name,
                'color' => $color
            ];
        }
        
        return $user;
    } catch (PDOException $e) {
        error_log("Error in getOrCreateUser: " . $e->getMessage());
        throw $e;
    }
}

// Helper function to broadcast update
function broadcastUpdate($pdo, $eventType, $eventData) {
    try {
        $stmt = $pdo->prepare("INSERT INTO calendar_updates (event_type, event_data) VALUES (?, ?)");
        $stmt->execute([$eventType, json_encode($eventData)]);
        
        // Clean up old updates periodically (keep only last 500)
        if (rand(1, 100) === 1) { // 1% chance to clean up
            $pdo->exec("DELETE FROM calendar_updates WHERE id < (SELECT id FROM (SELECT id FROM calendar_updates ORDER BY id DESC LIMIT 1 OFFSET 500) AS t)");
        }
    } catch (PDOException $e) {
        error_log("Error in broadcastUpdate: " . $e->getMessage());
        // Don't throw here as this shouldn't break the main operation
    }
}

// Test database connection
function testDatabaseConnection($pdo) {
    try {
        $stmt = $pdo->query("SELECT 1");
        return true;
    } catch (PDOException $e) {
        error_log("Database test failed: " . $e->getMessage());
        return false;
    }
}
?>