<?php
/**
 * Event Updates Cleanup Script
 * Location: backend/cleanup_event_updates.php
 * 
 * This script fixes the infinite SSE loop by cleaning up the event_updates table
 * and provides detailed information about the cleanup process.
 */

require_once 'database/config.php';

echo "Event Updates Cleanup Script\n";
echo "============================\n\n";

try {
    // Check if event_updates table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'event_updates'");
    if (!$stmt->fetch()) {
        echo "ERROR: event_updates table does not exist!\n";
        echo "Creating the table...\n";
        
        // Create the table using CalendarUpdate model
        $calendarUpdate->createTable();
        echo "✓ event_updates table created successfully\n";
        exit(0);
    }
    
    echo "✓ event_updates table exists\n\n";
    
    // Get current statistics
    echo "CURRENT TABLE STATUS:\n";
    echo "-------------------\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM event_updates");
    $totalCount = $stmt->fetchColumn();
    echo "Total records: $totalCount\n";
    
    if ($totalCount > 0) {
        // Show age distribution
        $stmt = $pdo->query("SELECT COUNT(*) FROM event_updates WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $recentCount = $stmt->fetchColumn();
        echo "Records from last hour: $recentCount\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM event_updates WHERE created_at BETWEEN DATE_SUB(NOW(), INTERVAL 24 HOUR) AND DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $oldCount = $stmt->fetchColumn();
        echo "Records 1-24 hours old: $oldCount\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM event_updates WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $veryOldCount = $stmt->fetchColumn();
        echo "Records older than 24 hours: $veryOldCount\n";
        
        // Show event type distribution
        echo "\nEvent type distribution:\n";
        $stmt = $pdo->query("SELECT event_type, COUNT(*) as count FROM event_updates GROUP BY event_type ORDER BY count DESC");
        while ($row = $stmt->fetch()) {
            echo "  {$row['event_type']}: {$row['count']}\n";
        }
        
        // Show recent records
        echo "\nRecent 10 records:\n";
        $stmt = $pdo->query("SELECT id, event_type, created_at FROM event_updates ORDER BY id DESC LIMIT 10");
        while ($row = $stmt->fetch()) {
            echo "  ID: {$row['id']}, Type: {$row['event_type']}, Created: {$row['created_at']}\n";
        }
        
        // Check for potential duplicates or problematic patterns
        echo "\nLooking for potential SSE loop causes:\n";
        $stmt = $pdo->query("
            SELECT event_type, JSON_EXTRACT(event_data, '$.id') as event_id, COUNT(*) as count 
            FROM event_updates 
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            GROUP BY event_type, JSON_EXTRACT(event_data, '$.id') 
            HAVING count > 5
            ORDER BY count DESC
            LIMIT 10
        ");
        $duplicates = $stmt->fetchAll();
        
        if ($duplicates) {
            echo "⚠️  Found potential duplicate broadcasts (same event multiple times in last hour):\n";
            foreach ($duplicates as $dup) {
                echo "  Event ID {$dup['event_id']} ({$dup['event_type']}): {$dup['count']} times\n";
            }
        } else {
            echo "✓ No obvious duplicate patterns found\n";
        }
    }
    
    echo "\nCLEANUP OPTIONS:\n";
    echo "================\n";
    echo "1. Light cleanup (remove records older than 24 hours)\n";
    echo "2. Moderate cleanup (keep only last 100 records)\n";
    echo "3. Full cleanup (truncate entire table)\n";
    echo "4. Custom cleanup (remove records older than X hours)\n";
    echo "5. Exit without cleanup\n\n";
    
    // If running non-interactively, default to light cleanup
    if (!defined('STDIN') || !is_resource(STDIN)) {
        echo "Running in non-interactive mode - performing light cleanup...\n";
        $choice = '1';
    } else {
        echo "Enter your choice (1-5): ";
        $choice = trim(fgets(STDIN));
    }
    
    switch ($choice) {
        case '1':
            echo "\nPerforming light cleanup (removing records older than 24 hours)...\n";
            $deletedCount = $calendarUpdate->simpleCleanup(24);
            echo "✓ Deleted $deletedCount old records\n";
            break;
            
        case '2':
            echo "\nPerforming moderate cleanup (keeping only last 100 records)...\n";
            $deletedCount = $calendarUpdate->cleanupOldUpdates(24, 100);
            echo "✓ Deleted $deletedCount old records\n";
            break;
            
        case '3':
            echo "\nPerforming full cleanup (truncating table)...\n";
            $deletedCount = $calendarUpdate->clearAllUpdates();
            echo "✓ Cleared all $deletedCount records from event_updates table\n";
            break;
            
        case '4':
            echo "Enter hours (records older than this will be deleted): ";
            $hours = (int)trim(fgets(STDIN));
            if ($hours > 0) {
                echo "\nPerforming custom cleanup (removing records older than $hours hours)...\n";
                $deletedCount = $calendarUpdate->simpleCleanup($hours);
                echo "✓ Deleted $deletedCount old records\n";
            } else {
                echo "Invalid input. Exiting.\n";
                exit(1);
            }
            break;
            
        case '5':
            echo "Exiting without cleanup.\n";
            exit(0);
            
        default:
            echo "Invalid choice. Exiting.\n";
            exit(1);
    }
    
    // Show final statistics
    echo "\nFINAL STATUS:\n";
    echo "=============\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM event_updates");
    $finalCount = $stmt->fetchColumn();
    echo "Total records after cleanup: $finalCount\n";
    
    if ($finalCount > 0) {
        $stmt = $pdo->query("SELECT MIN(created_at) as oldest, MAX(created_at) as newest FROM event_updates");
        $dateRange = $stmt->fetch();
        echo "Date range: {$dateRange['oldest']} to {$dateRange['newest']}\n";
    }
    
    echo "\n✓ Cleanup completed successfully!\n";
    echo "\nRecommendations to prevent future SSE loops:\n";
    echo "- Monitor the event_updates table size regularly\n";
    echo "- Ensure the CalendarUpdate::cleanupOldUpdates() method is called periodically\n";
    echo "- Consider implementing a cron job to run cleanup daily\n";
    echo "- Check SSE client implementations for proper lastEventId handling\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

?>