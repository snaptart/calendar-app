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
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
}

// Helper function to get or create user
function getOrCreateUser($pdo, $name) {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE name = ?");
    $stmt->execute([$name]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Create new user with random color
        $colors = ['#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#34495e', '#e67e22'];
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
}

// Helper function to broadcast update
function broadcastUpdate($pdo, $eventType, $eventData) {
    $stmt = $pdo->prepare("INSERT INTO calendar_updates (event_type, event_data) VALUES (?, ?)");
    $stmt->execute([$eventType, json_encode($eventData)]);
}
?>