<?php

require_once __DIR__ . '/../src/Database.php';

// Retention period in days
$retentionDays = 30;

echo "Starting cleanup process...\n";
echo "Retention Policy: Delete events older than $retentionDays days.\n";

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Calculate cutoff timestamp
    $cutoff = time() - ($retentionDays * 86400);
    
    // Count events to be deleted
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE timestamp < :cutoff");
    $stmt->execute([':cutoff' => $cutoff]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        // Delete events
        $delStmt = $pdo->prepare("DELETE FROM events WHERE timestamp < :cutoff");
        $delStmt->execute([':cutoff' => $cutoff]);
        echo "Deleted $count old events.\n";
        
        // Optimize database (vacuum) to reclaim space
        $pdo->exec("VACUUM");
        echo "Database vacuumed.\n";
    } else {
        echo "No events found older than $retentionDays days.\n";
    }
    
} catch (Exception $e) {
    echo "Error during cleanup: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Cleanup completed successfully.\n";
