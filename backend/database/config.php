<?php
/**
 * Database configuration and helper functions - FIXED VERSION
 * Location: backend/database/config.php
 * 
 * This file handles database connection and provides helper functions
 * for user management and real-time update broadcasting.
 * 
 * FIXES:
 * - Prevents duplicate broadcasts
 * - Improved error handling
 * - Better connection management
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

// Track recent broadcasts to prevent duplicates
$recentBroadcasts = [];

/**
 * Helper function to get or create user
 * 
 * @param PDO $pdo Database connection
 * @param string $name User name
 * @return array User data
 * @throws PDOException
 */
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

/**
 * Helper function to broadcast update via SSE with duplicate prevention
 * 
 * @param PDO $pdo Database connection
 * @param string $eventType Type of event (create, update, delete)
 * @param array $eventData Event data to broadcast
 */
function broadcastUpdate($pdo, $eventType, $eventData) {
    global $recentBroadcasts;
    
    try {
        // Create a hash to identify duplicate broadcasts
        $eventHash = md5($eventType . json_encode($eventData) . time());
        
        // Check if this exact event was recently broadcast (within last 5 seconds)
        $currentTime = time();
        $recentBroadcasts = array_filter($recentBroadcasts, function($broadcast) use ($currentTime) {
            return ($currentTime - $broadcast['timestamp']) < 5; // Keep only last 5 seconds
        });
        
        // Check for duplicates
        foreach ($recentBroadcasts as $broadcast) {
            if ($broadcast['type'] === $eventType && 
                isset($eventData['id']) && isset($broadcast['data']['id']) && 
                $eventData['id'] === $broadcast['data']['id']) {
                error_log("Preventing duplicate broadcast for {$eventType} event ID {$eventData['id']}");
                return; // Skip duplicate broadcast
            }
        }
        
        // Add to recent broadcasts tracking
        $recentBroadcasts[] = [
            'hash' => $eventHash,
            'type' => $eventType,
            'data' => $eventData,
            'timestamp' => $currentTime
        ];
        
        // Insert the update into database
        $stmt = $pdo->prepare("INSERT INTO calendar_updates (event_type, event_data) VALUES (?, ?)");
        $stmt->execute([$eventType, json_encode($eventData)]);
        
        error_log("Broadcast update: {$eventType} for event ID " . ($eventData['id'] ?? 'N/A'));
        
        // Clean up old updates more aggressively to prevent bloat
        if (rand(1, 50) === 1) { // 2% chance to clean up (more frequent)
            $deleteStmt = $pdo->prepare("DELETE FROM calendar_updates WHERE id < (SELECT id FROM (SELECT id FROM calendar_updates ORDER BY id DESC LIMIT 1 OFFSET 100) AS t)");
            $deleteStmt->execute();
            $deletedCount = $deleteStmt->rowCount();
            if ($deletedCount > 0) {
                error_log("Cleaned up $deletedCount old calendar updates");
            }
        }
        
    } catch (PDOException $e) {
        error_log("Error in broadcastUpdate: " . $e->getMessage());
        // Don't throw here as this shouldn't break the main operation
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
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if (!$stmt->fetch()) {
            throw new Exception("Database tables not found. Please import the SQL schema from documentation/calendar-app.sql");
        }
        
        $stmt = $pdo->query("SHOW TABLES LIKE 'events'");
        if (!$stmt->fetch()) {
            throw new Exception("Database tables not found. Please import the SQL schema from documentation/calendar-app.sql");
        }
        
        $stmt = $pdo->query("SHOW TABLES LIKE 'calendar_updates'");
        if (!$stmt->fetch()) {
            throw new Exception("Database tables not found. Please import the SQL schema from documentation/calendar-app.sql");
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Database initialization check failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Clear old calendar updates that might be causing issues
 * 
 * @param PDO $pdo Database connection
 */
function clearOldUpdates($pdo) {
    try {
        // Delete updates older than 1 hour
        $stmt = $pdo->prepare("DELETE FROM calendar_updates WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt->execute();
        $deletedCount = $stmt->rowCount();
        
        if ($deletedCount > 0) {
            error_log("Cleared $deletedCount old calendar updates");
        }
        
        return $deletedCount;
    } catch (PDOException $e) {
        error_log("Error clearing old updates: " . $e->getMessage());
        return 0;
    }
}

// Perform basic database validation
if (!testDatabaseConnection($pdo)) {
    error_log("Database connection test failed");
    die(json_encode(['error' => 'Database connection test failed']));
}

// Optional: Check if tables exist (useful for development)
if (!initializeDatabaseTables($pdo)) {
    error_log("Database tables validation failed");
    // Note: We don't die here to allow the application to continue running
    // The error will be logged for debugging purposes
}

// Optional: Clear old updates on connection to prevent buildup
if (rand(1, 20) === 1) { // 5% chance to clean up old updates
    clearOldUpdates($pdo);
}
?>