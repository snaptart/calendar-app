<?php
/**
 * Updated Database Configuration - Refactored Version
 * Location: backend/database/config.php
 * 
 * This file handles database connection and provides the legacy broadcastUpdate
 * function for backward compatibility with existing SSE code.
 * 
 * The broadcastUpdate function now delegates to the CalendarUpdate model.
 */

// Database configuration
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

// Initialize CalendarUpdate model for legacy compatibility
require_once __DIR__ . '/../models/CalendarUpdate.php';
$calendarUpdate = new CalendarUpdate($pdo);
$calendarUpdate->initialize();

/**
 * Legacy broadcastUpdate function for backward compatibility
 * This delegates to the CalendarUpdate model
 * 
 * @param PDO $pdo Database connection (ignored, kept for compatibility)
 * @param string $eventType Type of event (create, update, delete)
 * @param array $eventData Event data to broadcast
 */
function broadcastUpdate($pdo, $eventType, $eventData) {
    global $calendarUpdate;
    
    try {
        return $calendarUpdate->broadcastUpdate($eventType, $eventData);
    } catch (Exception $e) {
        error_log("Legacy broadcastUpdate error: " . $e->getMessage());
        return false;
    }
}

/**
 * Helper function to get or create user (updated to use User model)
 * 
 * @param PDO $pdo Database connection
 * @param string $name User name
 * @return array User data
 * @throws PDOException
 */
function getOrCreateUser($pdo, $name) {
    try {
        // For backward compatibility, we'll still use direct database queries
        // but this should ideally use the User model
        
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

/**
 * Test database connection
 * 
 * @param PDO $pdo Database connection
 * @return bool True if connection is working
 */
function testDatabaseConnection($pdo) {
    try {
        $stmt = $pdo->query("SELECT 1");
        return true;
    } catch (PDOException $e) {
        error_log("Database test failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Initialize database tables if they don't exist
 * This is a safety function for development environments
 */
function initializeDatabaseTables($pdo) {
    try {
        // Check if tables exist
        $tables = ['users', 'events', 'calendar_updates', 'user_sessions'];
        
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
            if (!$stmt->fetch()) {
                throw new Exception("Database table '{$table}' not found. Please import the SQL schema from documentation/calendar-app.sql");
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Database initialization check failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Clear old calendar updates using the CalendarUpdate model
 * 
 * @param PDO $pdo Database connection (kept for compatibility)
 */
function clearOldUpdates($pdo) {
    global $calendarUpdate;
    
    try {
        return $calendarUpdate->cleanupOldUpdates();
    } catch (Exception $e) {
        error_log("Error clearing old updates: " . $e->getMessage());
        return 0;
    }
}

// Perform basic database validation
if (!testDatabaseConnection($pdo)) {
    error_log("Database connection test failed");
    die(json_encode(['error' => 'Database connection test failed']));
}

// Check if tables exist (useful for development)
if (!initializeDatabaseTables($pdo)) {
    error_log("Database tables validation failed");
    // Note: We don't die here to allow the application to continue running
    // The error will be logged for debugging purposes
}

// Optional: Clear old updates on connection to prevent buildup
if (rand(1, 20) === 1) { // 5% chance to clean up old updates
    clearOldUpdates($pdo);
}

/**
 * Get database statistics
 * 
 * @return array Database statistics
 */
function getDatabaseStats() {
    global $pdo, $calendarUpdate;
    
    try {
        $stats = [];
        
        // User count
        $stmt = $pdo->query("SELECT COUNT(*) as user_count FROM users");
        $stats['users'] = $stmt->fetch()['user_count'];
        
        // Event count
        $stmt = $pdo->query("SELECT COUNT(*) as event_count FROM events");
        $stats['events'] = $stmt->fetch()['event_count'];
        
        // Update stats
        $updateStats = $calendarUpdate->getUpdateStats();
        $stats['updates'] = $updateStats;
        
        // Session count
        $stmt = $pdo->query("SELECT COUNT(*) as session_count FROM user_sessions WHERE is_active = 1 AND expires_at > NOW()");
        $stats['active_sessions'] = $stmt->fetch()['session_count'];
        
        return $stats;
        
    } catch (Exception $e) {
        error_log("Error getting database stats: " . $e->getMessage());
        return [];
    }
}

/**
 * Initialize all models for external use
 * 
 * @return array Array of initialized models
 */
function initializeModels() {
    global $pdo, $calendarUpdate;
    
    require_once __DIR__ . '/../models/User.php';
    require_once __DIR__ . '/../models/Event.php';
    
    $userModel = new User($pdo, $calendarUpdate);
    $eventModel = new Event($pdo, $calendarUpdate);
    
    return [
        'pdo' => $pdo,
        'user' => $userModel,
        'event' => $eventModel,
        'calendarUpdate' => $calendarUpdate
    ];
}
?>