<?php
/**
 * Updated Database Configuration for itmdev
 * Location: backend/database/config.php
 * 
 * This file handles database connection for the itmdev ice time management system
 * and provides legacy broadcastUpdate function for backward compatibility.
 */

// Database configuration
$host = 'localhost';
$dbname = 'itmdev';  // Changed from 'collaborative_calendar'
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
 * Helper function to get or create user (updated for itmdev user table)
 * 
 * @param PDO $pdo Database connection
 * @param string $name User name
 * @return array User data
 * @throws PDOException
 */
function getOrCreateUser($pdo, $name) {
    try {
        // Check if user exists in new user table
        $stmt = $pdo->prepare("SELECT * FROM user WHERE user_Name = ?");
        $stmt->execute([$name]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // Create new user with random color and default role
            $colors = ['#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#34495e', '#e67e22', '#3498db'];
            $color = $colors[array_rand($colors)];
            
            // Get the Calendar User role ID (or create it)
            $roleStmt = $pdo->prepare("SELECT role_ID FROM role WHERE role_Name = 'Calendar User'");
            $roleStmt->execute();
            $role = $roleStmt->fetch();
            
            if (!$role) {
                // Create default Calendar User role
                $createRoleStmt = $pdo->prepare("
                    INSERT INTO role (role_Name, role_Desc, role_Level, role_Entity, created_by) 
                    VALUES ('Calendar User', 'Imported from calendar-app', 10, 'calendar', 'migration')
                ");
                $createRoleStmt->execute();
                $roleId = $pdo->lastInsertId();
            } else {
                $roleId = $role['role_ID'];
            }
            
            // Generate unique email if not provided
            $email = "user{$name}@example.com";
            $email = strtolower(str_replace(' ', '', $email));
            
            $stmt = $pdo->prepare("
                INSERT INTO user (role_ID, user_Name, user_Email, password, user_Status, created_by) 
                VALUES (?, ?, ?, '', 'Active', 'calendar_migration')
            ");
            $stmt->execute([$roleId, $name, $email]);
            
            $user = [
                'user_ID' => $pdo->lastInsertId(),
                'user_Name' => $name,
                'user_Email' => $email,
                'color' => $color,  // This will be handled in frontend
                'role_ID' => $roleId
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
 * Updated for itmdev schema
 */
function initializeDatabaseTables($pdo) {
    try {
        // Check if required tables exist
        $tables = ['user', 'event', 'episode', 'event_updates', 'session', 'role', 'facility', 'program', 'team'];
        
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
            if (!$stmt->fetch()) {
                throw new Exception("Database table '{$table}' not found. Please import the SQL schema from documentation/itmdev.sql");
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Database initialization check failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Clear old event updates using the CalendarUpdate model
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

/**
 * Get default facility ID for events
 * 
 * @return int Default facility ID
 */
function getDefaultFacilityId() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT facility_ID FROM facility ORDER BY facility_ID LIMIT 1");
        $facility = $stmt->fetch();
        return $facility ? $facility['facility_ID'] : 1;
    } catch (Exception $e) {
        error_log("Error getting default facility: " . $e->getMessage());
        return 1;
    }
}

/**
 * Get default program ID for events
 * 
 * @return int Default program ID
 */
function getDefaultProgramId() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT program_ID FROM program ORDER BY program_ID LIMIT 1");
        $program = $stmt->fetch();
        return $program ? $program['program_ID'] : 1;
    } catch (Exception $e) {
        error_log("Error getting default program: " . $e->getMessage());
        return 1;
    }
}

/**
 * Get default team ID for a user
 * 
 * @param int $userId User ID
 * @return int Default team ID
 */
function getDefaultTeamId($userId = null) {
    global $pdo;
    
    try {
        // Try to find a team associated with the user's program
        $stmt = $pdo->query("SELECT team_ID FROM team ORDER BY team_ID LIMIT 1");
        $team = $stmt->fetch();
        return $team ? $team['team_ID'] : 1;
    } catch (Exception $e) {
        error_log("Error getting default team: " . $e->getMessage());
        return 1;
    }
}

/**
 * Get default resource ID for events
 * 
 * @return int Default resource ID
 */
function getDefaultResourceId() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT resource_ID FROM resource ORDER BY resource_ID LIMIT 1");
        $resource = $stmt->fetch();
        return $resource ? $resource['resource_ID'] : 1;
    } catch (Exception $e) {
        error_log("Error getting default resource: " . $e->getMessage());
        return 1;
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
 * Get database statistics for itmdev
 * 
 * @return array Database statistics
 */
function getDatabaseStats() {
    global $pdo, $calendarUpdate;
    
    try {
        $stats = [];
        
        // User count
        $stmt = $pdo->query("SELECT COUNT(*) as user_count FROM user");
        $stats['users'] = $stmt->fetch()['user_count'];
        
        // Event count (events table)
        $stmt = $pdo->query("SELECT COUNT(*) as event_count FROM event");
        $stats['events'] = $stmt->fetch()['event_count'];
        
        // Episode count (actual event instances)
        $stmt = $pdo->query("SELECT COUNT(*) as episode_count FROM episode");
        $stats['episodes'] = $stmt->fetch()['episode_count'];
        
        // Update stats
        $updateStats = $calendarUpdate->getUpdateStats();
        $stats['updates'] = $updateStats;
        
        // Session count
        $stmt = $pdo->query("SELECT COUNT(*) as session_count FROM session WHERE is_active = 1 AND expires_at > NOW()");
        $stats['active_sessions'] = $stmt->fetch()['session_count'];
        
        // Facility count
        $stmt = $pdo->query("SELECT COUNT(*) as facility_count FROM facility WHERE facility_Status = 'Active'");
        $stats['facilities'] = $stmt->fetch()['facility_count'];
        
        // Program count
        $stmt = $pdo->query("SELECT COUNT(*) as program_count FROM program WHERE program_Status = 'Active'");
        $stats['programs'] = $stmt->fetch()['program_count'];
        
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