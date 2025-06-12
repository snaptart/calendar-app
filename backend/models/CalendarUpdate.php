<?php
/**
 * CalendarUpdate Model Class for itmdev
 * Location: backend/models/CalendarUpdate.php
 * 
 * Updated to use event_updates table instead of calendar_updates
 */

class CalendarUpdate {
    private $pdo;
    private $recentBroadcasts = [];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Broadcast update via SSE with duplicate prevention
     * 
     * @param string $eventType Type of event (create, update, delete)
     * @param array $eventData Event data to broadcast
     * @return bool Success status
     */
    public function broadcastUpdate($eventType, $eventData) {
        try {
            // Check for duplicates
            if ($this->isDuplicateBroadcast($eventType, $eventData)) {
                error_log("Preventing duplicate broadcast for {$eventType} event ID " . ($eventData['id'] ?? 'N/A'));
                return false;
            }
            
            // Add to recent broadcasts tracking
            $this->trackBroadcast($eventType, $eventData);
            
            // Insert the update into event_updates table
            $stmt = $this->pdo->prepare("INSERT INTO event_updates (event_type, event_data) VALUES (?, ?)");
            $stmt->execute([$eventType, json_encode($eventData)]);
            
            error_log("Broadcast update: {$eventType} for event ID " . ($eventData['id'] ?? 'N/A'));
            
            // Clean up old updates periodically
            $this->cleanupOldUpdates();
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Error in broadcastUpdate: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get recent calendar updates
     * 
     * @param int $lastId Last received update ID
     * @param int $limit Maximum number of updates to return
     * @return array Array of updates
     */
    public function getUpdates($lastId = 0, $limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM event_updates 
                WHERE id > ? 
                ORDER BY id ASC 
                LIMIT ?
            ");
            $stmt->execute([$lastId, $limit]);
            
            $updates = $stmt->fetchAll();
            
            // Decode event data
            return array_map(function($update) {
                return [
                    'id' => (int)$update['id'],
                    'event_type' => $update['event_type'],
                    'event_data' => json_decode($update['event_data'], true),
                    'created_at' => $update['created_at']
                ];
            }, $updates);
            
        } catch (PDOException $e) {
            error_log("Error fetching calendar updates: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get the latest update ID
     * 
     * @return int Latest update ID
     */
    public function getLatestUpdateId() {
        try {
            $stmt = $this->pdo->query("SELECT MAX(id) FROM event_updates");
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error fetching latest update ID: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Clean up old calendar updates
     * 
     * @param int $maxAge Maximum age in hours (default: 24 hours)
     * @param int $maxRecords Maximum number of records to keep (default: 1000)
     * @return int Number of deleted records
     */
    public function cleanupOldUpdates($maxAge = 24, $maxRecords = 1000) {
        try {
            $deletedCount = 0;
            
            // Delete updates older than specified hours
            $stmt = $this->pdo->prepare("
                DELETE FROM event_updates 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? HOUR)
            ");
            $stmt->execute([$maxAge]);
            $deletedCount += $stmt->rowCount();
            
            // Keep only the most recent X records using direct SQL
            $maxRecords = (int)$maxRecords; // Ensure it's an integer
            
            $sql = "
                DELETE FROM event_updates 
                WHERE id NOT IN (
                    SELECT id FROM (
                        SELECT id FROM event_updates 
                        ORDER BY id DESC 
                        LIMIT {$maxRecords}
                    ) AS recent_updates
                )
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $deletedCount += $stmt->rowCount();
            
            if ($deletedCount > 0) {
                error_log("Cleaned up {$deletedCount} old event updates");
            }
            
            return $deletedCount;
            
        } catch (PDOException $e) {
            error_log("Error cleaning up event updates: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Force cleanup of all calendar updates
     * 
     * @return int Number of deleted records
     */
    public function clearAllUpdates() {
        try {
            $stmt = $this->pdo->query("DELETE FROM event_updates");
            $deletedCount = $stmt->rowCount();
            
            error_log("Cleared all event updates: {$deletedCount} records");
            
            return $deletedCount;
            
        } catch (PDOException $e) {
            error_log("Error clearing event updates: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get calendar update statistics
     * 
     * @return array Statistics about calendar updates
     */
    public function getUpdateStats() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    COUNT(*) as total_updates,
                    COUNT(CASE WHEN event_type = 'create' THEN 1 END) as create_updates,
                    COUNT(CASE WHEN event_type = 'update' THEN 1 END) as update_updates,
                    COUNT(CASE WHEN event_type = 'delete' THEN 1 END) as delete_updates,
                    MIN(created_at) as oldest_update,
                    MAX(created_at) as newest_update
                FROM event_updates
            ");
            
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("Error fetching update stats: " . $e->getMessage());
            return [
                'total_updates' => 0,
                'create_updates' => 0,
                'update_updates' => 0,
                'delete_updates' => 0,
                'oldest_update' => null,
                'newest_update' => null
            ];
        }
    }
    
    /**
     * Get updates for a specific event
     * 
     * @param int $eventId Event ID
     * @param int $limit Maximum number of updates to return
     * @return array Array of updates for the event
     */
    public function getEventUpdates($eventId, $limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM event_updates 
                WHERE JSON_EXTRACT(event_data, '$.id') = ?
                ORDER BY id DESC 
                LIMIT ?
            ");
            $stmt->execute([$eventId, $limit]);
            
            $updates = $stmt->fetchAll();
            
            return array_map(function($update) {
                return [
                    'id' => (int)$update['id'],
                    'event_type' => $update['event_type'],
                    'event_data' => json_decode($update['event_data'], true),
                    'created_at' => $update['created_at']
                ];
            }, $updates);
            
        } catch (PDOException $e) {
            error_log("Error fetching event updates: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Broadcast a custom update
     * 
     * @param string $eventType Custom event type
     * @param array $eventData Custom event data
     * @return bool Success status
     */
    public function broadcastCustomUpdate($eventType, $eventData) {
        return $this->broadcastUpdate($eventType, $eventData);
    }
    
    /**
     * Broadcast system notification
     * 
     * @param string $message Notification message
     * @param string $type Notification type (info, warning, error)
     * @param array $additionalData Additional data
     * @return bool Success status
     */
    public function broadcastNotification($message, $type = 'info', $additionalData = []) {
        $notificationData = array_merge([
            'message' => $message,
            'type' => $type,
            'timestamp' => time()
        ], $additionalData);
        
        return $this->broadcastUpdate('notification', $notificationData);
    }
    
    /**
     * Broadcast user activity (join, leave, etc.)
     * 
     * @param int $userId User ID
     * @param string $activity Activity type (join, leave, etc.)
     * @param array $additionalData Additional data
     * @return bool Success status
     */
    public function broadcastUserActivity($userId, $activity, $additionalData = []) {
        $activityData = array_merge([
            'userId' => $userId,
            'activity' => $activity,
            'timestamp' => time()
        ], $additionalData);
        
        return $this->broadcastUpdate('user_activity', $activityData);
    }
    
    /**
     * Check if a broadcast is duplicate
     * 
     * @param string $eventType Event type
     * @param array $eventData Event data
     * @return bool True if duplicate, false otherwise
     */
    private function isDuplicateBroadcast($eventType, $eventData) {
        $currentTime = time();
        
        // Clean up old broadcasts (keep only last 5 seconds)
        $this->recentBroadcasts = array_filter($this->recentBroadcasts, function($broadcast) use ($currentTime) {
            return ($currentTime - $broadcast['timestamp']) < 5;
        });
        
        // Check for duplicates in memory
        foreach ($this->recentBroadcasts as $broadcast) {
            if ($broadcast['type'] === $eventType && 
                isset($eventData['id']) && isset($broadcast['data']['id']) && 
                $eventData['id'] === $broadcast['data']['id']) {
                return true;
            }
        }
        
        // Also check for recent duplicates in database (last 10 seconds)
        if (isset($eventData['id'])) {
            try {
                $stmt = $this->pdo->prepare("
                    SELECT COUNT(*) FROM event_updates 
                    WHERE event_type = ? 
                    AND JSON_EXTRACT(event_data, '$.id') = ? 
                    AND created_at > DATE_SUB(NOW(), INTERVAL 10 SECOND)
                ");
                $stmt->execute([$eventType, $eventData['id']]);
                $recentCount = $stmt->fetchColumn();
                
                if ($recentCount > 0) {
                    error_log("Preventing database duplicate: {$eventType} for event ID {$eventData['id']} (found {$recentCount} recent records)");
                    return true;
                }
            } catch (Exception $e) {
                error_log("Error checking database duplicates: " . $e->getMessage());
                // Continue with broadcast if database check fails
            }
        }
        
        return false;
    }
    
    /**
     * Track a broadcast to prevent duplicates
     * 
     * @param string $eventType Event type
     * @param array $eventData Event data
     */
    private function trackBroadcast($eventType, $eventData) {
        $this->recentBroadcasts[] = [
            'type' => $eventType,
            'data' => $eventData,
            'timestamp' => time()
        ];
    }
    
    /**
     * Check if the event_updates table exists
     * 
     * @return bool True if table exists, false otherwise
     */
    public function tableExists() {
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'event_updates'");
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("Error checking table existence: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create the event_updates table if it doesn't exist
     * 
     * @return bool Success status
     */
    public function createTable() {
        try {
            $sql = "
                CREATE TABLE IF NOT EXISTS event_updates (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    event_type VARCHAR(50) NOT NULL,
                    event_data TEXT NOT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ";
            
            $this->pdo->exec($sql);
            error_log("Event updates table created successfully");
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Error creating event_updates table: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Initialize the calendar updates system
     * 
     * @return bool Success status
     */
    public function initialize() {
        if (!$this->tableExists()) {
            return $this->createTable();
        }
        
        // Perform initial cleanup
        $this->cleanupOldUpdates();
        
        return true;
    }
    
    /**
     * Simple cleanup that just removes old records by age
     * 
     * @param int $maxAge Maximum age in hours
     * @return int Number of deleted records
     */
    public function simpleCleanup($maxAge = 24) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM event_updates 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? HOUR)
            ");
            $stmt->execute([$maxAge]);
            
            $deletedCount = $stmt->rowCount();
            
            if ($deletedCount > 0) {
                error_log("Simple cleanup: Removed {$deletedCount} old event updates");
            }
            
            return $deletedCount;
            
        } catch (PDOException $e) {
            error_log("Error in simple cleanup: " . $e->getMessage());
            return 0;
        }
    }
}
?>