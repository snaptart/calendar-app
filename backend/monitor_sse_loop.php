<?php
/**
 * SSE Loop Monitor Script
 * Location: backend/monitor_sse_loop.php
 * 
 * This script monitors the event_updates table in real-time to help
 * identify what's causing the SSE infinite loop.
 */

require_once 'database/config.php';

echo "SSE Loop Monitor\n";
echo "================\n\n";

// Configuration
$monitorDuration = 60; // Monitor for 60 seconds
$checkInterval = 2;    // Check every 2 seconds
$maxRecordsToShow = 5; // Show max 5 new records per check

$startTime = time();
$lastId = 0;
$totalNewRecords = 0;
$eventCounts = [];

// Get initial state
try {
    $stmt = $pdo->query("SELECT MAX(id) FROM event_updates");
    $lastId = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM event_updates");
    $initialCount = $stmt->fetchColumn();
    
    echo "Initial state:\n";
    echo "- Total records: $initialCount\n";
    echo "- Last ID: $lastId\n";
    echo "- Monitoring for $monitorDuration seconds...\n\n";
    
} catch (Exception $e) {
    echo "ERROR: Could not get initial state: " . $e->getMessage() . "\n";
    exit(1);
}

// Monitor loop
echo "Timestamp          | New Records | Event Types | Details\n";
echo "-------------------|-------------|-------------|--------------------\n";

while (time() - $startTime < $monitorDuration) {
    try {
        // Check for new records
        $stmt = $pdo->prepare("SELECT * FROM event_updates WHERE id > ? ORDER BY id ASC");
        $stmt->execute([$lastId]);
        $newRecords = $stmt->fetchAll();
        
        if (!empty($newRecords)) {
            $newCount = count($newRecords);
            $totalNewRecords += $newCount;
            
            // Count event types
            $typeCounts = [];
            foreach ($newRecords as $record) {
                $type = $record['event_type'];
                if (!isset($typeCounts[$type])) {
                    $typeCounts[$type] = 0;
                }
                $typeCounts[$type]++;
                
                // Track overall event counts
                if (!isset($eventCounts[$type])) {
                    $eventCounts[$type] = 0;
                }
                $eventCounts[$type]++;
            }
            
            // Format type counts
            $typeString = '';
            foreach ($typeCounts as $type => $count) {
                $typeString .= "$type($count) ";
            }
            
            // Show details for first few records
            $details = '';
            $recordsToShow = array_slice($newRecords, 0, $maxRecordsToShow);
            foreach ($recordsToShow as $record) {
                $eventData = json_decode($record['event_data'], true);
                $eventId = isset($eventData['id']) ? $eventData['id'] : 'N/A';
                $details .= "ID{$eventId} ";
            }
            if (count($newRecords) > $maxRecordsToShow) {
                $details .= '...';
            }
            
            printf("%-18s | %-11d | %-11s | %s\n", 
                date('H:i:s'), 
                $newCount, 
                trim($typeString), 
                $details
            );
            
            // Update last ID
            $lastRecord = end($newRecords);
            $lastId = $lastRecord['id'];
            
            // Check for rapid-fire duplicates (potential loop indicator)
            if ($newCount > 10) {
                echo "⚠️  ALERT: $newCount records added in one check - possible loop detected!\n";
            }
            
            // Check for identical events in quick succession
            $recentDuplicates = [];
            foreach ($newRecords as $record) {
                $eventData = json_decode($record['event_data'], true);
                if (isset($eventData['id'])) {
                    $key = $record['event_type'] . '_' . $eventData['id'];
                    if (!isset($recentDuplicates[$key])) {
                        $recentDuplicates[$key] = 0;
                    }
                    $recentDuplicates[$key]++;
                }
            }
            
            foreach ($recentDuplicates as $key => $count) {
                if ($count > 1) {
                    echo "⚠️  DUPLICATE: $key appeared $count times in this batch\n";
                }
            }
        }
        
        // Sleep before next check
        sleep($checkInterval);
        
    } catch (Exception $e) {
        echo "ERROR during monitoring: " . $e->getMessage() . "\n";
        break;
    }
}

// Final summary
echo "\n\nMONITORING SUMMARY:\n";
echo "===================\n";
echo "Duration: " . (time() - $startTime) . " seconds\n";
echo "Total new records: $totalNewRecords\n";
echo "Average rate: " . round($totalNewRecords / max(1, time() - $startTime), 2) . " records/second\n";

if (!empty($eventCounts)) {
    echo "\nEvent type summary:\n";
    arsort($eventCounts);
    foreach ($eventCounts as $type => $count) {
        echo "- $type: $count\n";
    }
}

// Get final state
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM event_updates");
    $finalCount = $stmt->fetchColumn();
    echo "\nFinal record count: $finalCount\n";
    echo "Net increase: " . ($finalCount - $initialCount) . "\n";
} catch (Exception $e) {
    echo "Could not get final count: " . $e->getMessage() . "\n";
}

// Recommendations
echo "\nRECOMMENDATIONS:\n";
if ($totalNewRecords > 100) {
    echo "⚠️  HIGH ACTIVITY: $totalNewRecords records in $monitorDuration seconds suggests a possible loop\n";
    echo "- Consider running emergency_cleanup.php to clear the table\n";
    echo "- Check SSE clients for proper lastEventId handling\n";
    echo "- Review event creation/update logic for duplicate triggers\n";
} elseif ($totalNewRecords > 20) {
    echo "⚠️  MODERATE ACTIVITY: $totalNewRecords records in $monitorDuration seconds\n";
    echo "- Monitor continued activity\n";
    echo "- Consider running cleanup_event_updates.php for maintenance\n";
} else {
    echo "✓ NORMAL ACTIVITY: $totalNewRecords records in $monitorDuration seconds appears normal\n";
}

?>