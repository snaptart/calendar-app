<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'calendar_app');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create database connection
function getDatabase() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }
    }
    
    return $pdo;
}

// Initialize database tables
function initializeDatabase() {
    $pdo = getDatabase();
    
    // Events table
    $sql = "CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_name VARCHAR(50) NOT NULL,
        title VARCHAR(200) NOT NULL,
        start_datetime DATETIME NOT NULL,
        end_datetime DATETIME NOT NULL,
        all_day BOOLEAN DEFAULT FALSE,
        color VARCHAR(7) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_name (user_name),
        INDEX idx_start_datetime (start_datetime),
        INDEX idx_id_updated (id, updated_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        error_log("Events table creation failed: " . $e->getMessage());
        die("Database initialization failed.");
    }
    
    // Event notifications table (MEMORY engine for performance)
    $sql = "CREATE TABLE IF NOT EXISTS event_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT,
        action ENUM('create', 'update', 'delete') NOT NULL,
        user_name VARCHAR(50) NOT NULL,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_id_timestamp (id, timestamp)
    ) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        error_log("Event notifications table creation failed: " . $e->getMessage());
        die("Database initialization failed.");
    }
}

// Generate a color for a user based on their name
function getUserColor($username) {
    $colors = [
        '#FF5733', '#33FF57', '#3357FF', '#FF33F1', '#F1FF33',
        '#33FFF1', '#F133FF', '#FF8C33', '#8CFF33', '#338CFF',
        '#FF3385', '#85FF33', '#3385FF', '#FF5C33', '#5CFF33'
    ];
    
    $hash = crc32($username);
    $index = abs($hash) % count($colors);
    return $colors[$index];
}

// Sanitize input data
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Validate username
function validateUsername($username) {
    return !empty($username) && strlen($username) <= 50 && preg_match('/^[a-zA-Z0-9\s\-_\.]+$/', $username);
}

// Validate event data
function validateEvent($title, $start, $end) {
    if (empty($title) || strlen($title) > 200) {
        return "Invalid title. Max 200 characters and cannot be empty.";
    }
    
    if (!$start || !$end) {
        return "Start and end times are required.";
    }
    
    $startTime = strtotime($start);
    $endTime = strtotime($end);
    
    if (!$startTime || !$endTime) {
        return "Invalid date/time format.";
    }
    
    if ($endTime <= $startTime) {
        return "End time must be after start time.";
    }
    
    return null; // No validation errors
}

// Add notification for real-time updates
function addEventNotification($pdo, $eventId, $action, $username) {
    try {
        $stmt = $pdo->prepare('INSERT INTO event_notifications (event_id, action, user_name) VALUES (?, ?, ?)');
        $stmt->execute([$eventId, $action, $username]);
    } catch (PDOException $e) {
        error_log("Failed to add event notification: " . $e->getMessage());
    }
}

// Initialize database when this file is included
initializeDatabase();
?>