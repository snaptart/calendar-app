<?php
/**
 * Cron Job Cleanup Script
 * Location: backend/cron_cleanup.php
 * 
 * This script is designed to be run as a cron job to automatically
 * maintain the event_updates table and prevent SSE loops.
 * 
 * Suggested cron schedule: */10 * * * * (every 10 minutes)
 */

require_once 'database/config.php';

// Only log if there's something to report
$logOutput = [];

try {
    // Get current stats
    $stmt = $pdo->query("SELECT COUNT(*) FROM event_updates");
    $currentCount = $stmt->fetchColumn();
    
    // Only proceed if there are records to potentially clean
    if ($currentCount > 0) {
        $logOutput[] = "Starting cleanup - current records: $currentCount";
        
        // Perform cleanup using CalendarUpdate model
        $deletedCount = $calendarUpdate->cleanupOldUpdates(2, 200); // Keep 200 records, delete older than 2 hours
        
        if ($deletedCount > 0) {
            $logOutput[] = "Cleaned up $deletedCount old records";
            
            // Get final count
            $stmt = $pdo->query("SELECT COUNT(*) FROM event_updates");
            $finalCount = $stmt->fetchColumn();
            $logOutput[] = "Final count: $finalCount records";
        } else {
            // Only log if there were many records but none were cleaned
            if ($currentCount > 500) {
                $logOutput[] = "Warning: $currentCount records present but none cleaned (may need manual intervention)";
            }
        }
        
        // Check for potential loop indicators
        $stmt = $pdo->query("
            SELECT COUNT(*) FROM event_updates 
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ");
        $recentCount = $stmt->fetchColumn();
        
        if ($recentCount > 100) {
            $logOutput[] = "Alert: $recentCount records created in last 5 minutes - possible SSE loop";
        }
        
        // Check for duplicate patterns
        $stmt = $pdo->query("
            SELECT event_type, JSON_EXTRACT(event_data, '$.id') as event_id, COUNT(*) as count 
            FROM event_updates 
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
            GROUP BY event_type, JSON_EXTRACT(event_data, '$.id') 
            HAVING count > 10
            LIMIT 5
        ");
        $duplicates = $stmt->fetchAll();
        
        if ($duplicates) {
            $logOutput[] = "Warning: Detected duplicate broadcasts:";
            foreach ($duplicates as $dup) {
                $eventId = $dup['event_id'] ?: 'N/A';
                $logOutput[] = "  Event ID $eventId ({$dup['event_type']}): {$dup['count']} times";
            }
        }
    }
    
    // Only output logs if there's something significant to report
    if (!empty($logOutput)) {
        $timestamp = date('Y-m-d H:i:s');
        echo "[$timestamp] Event Updates Cron Cleanup:\n";
        foreach ($logOutput as $line) {
            echo "  $line\n";
        }
        
        // Also log to error log for persistence
        error_log("Event Updates Cron: " . implode('; ', $logOutput));
    }
    
    exit(0);
    
} catch (Exception $e) {
    $errorMsg = "Cron cleanup error: " . $e->getMessage();
    echo "[$timestamp] ERROR: $errorMsg\n";
    error_log($errorMsg);
    exit(1);
}

?>