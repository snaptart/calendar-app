<?php
/**
 * Emergency Event Updates Cleanup Script
 * Location: backend/emergency_cleanup.php
 * 
 * This script immediately truncates the event_updates table to stop
 * the infinite SSE loop. Use this for immediate relief.
 */

require_once 'database/config.php';

echo "Emergency Event Updates Cleanup\n";
echo "===============================\n\n";

try {
    // Check current count
    $stmt = $pdo->query("SELECT COUNT(*) FROM event_updates");
    $count = $stmt->fetchColumn();
    
    echo "Current records in event_updates table: $count\n";
    
    if ($count == 0) {
        echo "✓ Table is already empty - no cleanup needed\n";
        exit(0);
    }
    
    // Truncate the table
    echo "Truncating event_updates table...\n";
    $pdo->exec("TRUNCATE TABLE event_updates");
    
    // Verify truncation
    $stmt = $pdo->query("SELECT COUNT(*) FROM event_updates");
    $newCount = $stmt->fetchColumn();
    
    if ($newCount == 0) {
        echo "✓ Successfully cleared $count records from event_updates table\n";
        echo "✓ SSE infinite loop should now be stopped\n";
        echo "\nNext steps:\n";
        echo "- Restart any running SSE connections\n";
        echo "- Monitor the table to ensure it doesn't fill up again\n";
        echo "- Consider running the full cleanup script for ongoing maintenance\n";
    } else {
        echo "⚠️  Warning: Table still contains $newCount records after truncation\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

?>