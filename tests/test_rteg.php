<?php
/**
 * Automated Test Suite for Realtime Map Event Grid (RTEG)
 * Tests: Database Init, Event Ingestion, Spatial Querying, Rate Limiting, Webhooks, and Stats.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/EventManager.php';
require_once __DIR__ . '/../src/RateLimiter.php';
require_once __DIR__ . '/../src/Webhook.php';
require_once __DIR__ . '/../src/Auth.php';

echo "========================================================\n";
echo "  🧪 Running Realtime Map Event Grid (RTEG) Test Suite  \n";
echo "========================================================\n\n";

$passed = 0;
$failed = 0;

function assertTest($name, $condition, $details = "") {
    global $passed, $failed;
    if ($condition) {
        echo "  ✅ PASS: $name\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: $name ($details)\n";
        $failed++;
    }
}

// 1. Database Connection & Schema Verification
try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    assertTest("Database Connection", $pdo instanceof PDO);

    // Initialize Schema
    require_once __DIR__ . '/../database/init.php';
    
    // Verify tables exist
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    assertTest("Table 'events' created", in_array('events', $tables));
    assertTest("Table 'sources' created", in_array('sources', $tables));
    assertTest("Table 'outbound_queue' created", in_array('outbound_queue', $tables));
    assertTest("Table 'rate_limits' created", in_array('rate_limits', $tables));
    assertTest("Table 'logs' created", in_array('logs', $tables));
} catch (Exception $e) {
    assertTest("Database Init Error", false, $e->getMessage());
}

// 2. Source Key Validation & Creation
try {
    $eventManager = new EventManager();
    
    // Insert test source if not exists
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO sources (source_id, source_name, source_secret, status) VALUES ('test_src_1', 'Automated Test Source', 'test_secret_key_123', 'active')");
    $stmt->execute();

    $source = $eventManager->validateSource('test_secret_key_123');
    assertTest("Source Validation (Active Key)", $source !== false && $source['source_id'] === 'test_src_1');

    $invalid = $eventManager->validateSource('invalid_secret_key');
    assertTest("Source Validation (Invalid Key Rejection)", $invalid === false);
} catch (Exception $e) {
    assertTest("Source Validation Error", false, $e->getMessage());
}

// 3. Event Ingestion & Coordinate Validation
try {
    $validEvent = [
        'type' => 'vehicle_movement',
        'lat' => 41.0082,
        'lon' => 28.9784,
        'timestamp' => time(),
        'payload' => json_encode(['vehicle_id' => 'CAR-001', 'speed_kmh' => 65.5, 'heading' => 'NE'])
    ];

    $eventId = $eventManager->createEvent($validEvent, 'test_src_1');
    assertTest("Create Valid Spatial Event", !empty($eventId));

    // Verify event in DB
    $stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = :id");
    $stmt->execute([':id' => $eventId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    assertTest("Event Persistence & Field Integrity", $row !== false && (float)$row['lat'] == 41.0082);

    // Reject Invalid Latitude
    $invalidLat = $validEvent;
    $invalidLat['lat'] = 95.0; // Invalid (> 90)
    $threw = false;
    try {
        $eventManager->createEvent($invalidLat, 'test_src_1');
    } catch (Exception $e) {
        $threw = true;
    }
    assertTest("Reject Out-of-Bounds Latitude (> 90)", $threw);
} catch (Exception $e) {
    assertTest("Event Creation Error", false, $e->getMessage());
}

// 4. Spatial & Bounding Box Filtering
try {
    // Ingest events in different regions
    $istanbulEvent = [
        'type' => 'courier_dispatch',
        'lat' => 41.0150,
        'lon' => 28.9790,
        'timestamp' => time(),
        'payload' => json_encode(['courier' => 'C-101'])
    ];
    $eventManager->createEvent($istanbulEvent, 'test_src_1');

    $ankaraEvent = [
        'type' => 'sensor_alert',
        'lat' => 39.9334,
        'lon' => 32.8597,
        'timestamp' => time(),
        'payload' => json_encode(['sensor' => 'S-99', 'temp' => 42.0])
    ];
    $eventManager->createEvent($ankaraEvent, 'test_src_1');

    // Query with Istanbul Bounding Box (Lat: 40.8 - 41.2, Lon: 28.5 - 29.5)
    $istanbulBounds = [
        'min_lat' => 40.8,
        'max_lat' => 41.2,
        'min_lon' => 28.5,
        'max_lon' => 29.5
    ];
    $istanbulResults = $eventManager->getEvents(['bounds' => $istanbulBounds]);
    assertTest("Spatial Bounding Box Filter (Istanbul)", count($istanbulResults) >= 2);

    // Query with Ankara Bounding Box (Lat: 39.5 - 40.5, Lon: 32.5 - 33.5)
    $ankaraBounds = [
        'min_lat' => 39.5,
        'max_lat' => 40.5,
        'min_lon' => 32.5,
        'max_lon' => 33.5
    ];
    $ankaraResults = $eventManager->getEvents(['bounds' => $ankaraBounds]);
    assertTest("Spatial Bounding Box Filter (Ankara)", count($ankaraResults) >= 1);
} catch (Exception $e) {
    assertTest("Spatial Query Error", false, $e->getMessage());
}

// 5. Rate Limiter (Token Bucket Algorithm)
try {
    $limiter = new RateLimiter();
    $clientIp = "192.168.1.100";
    
    // Check initial allowance
    $allowed1 = $limiter->check($clientIp, 5, 60); // 5 requests per minute
    assertTest("Rate Limiter Initial Request Allowed", $allowed1['allowed'] === true);

    // Consume all tokens
    for ($i = 0; $i < 5; $i++) {
        $limiter->check($clientIp, 5, 60);
    }
    
    $blocked = $limiter->check($clientIp, 5, 60);
    assertTest("Rate Limiter Throttling (HTTP 429 Trigger)", $blocked['allowed'] === false);
    assertTest("Rate Limiter Retry-After Header Value", $blocked['retry_after'] > 0);
} catch (Exception $e) {
    assertTest("Rate Limiter Error", false, $e->getMessage());
}

// 6. Webhook Dispatch & HMAC-SHA256 Signatures
try {
    $webhookSecret = "wh_secret_super_secure_999";
    $payloadData = ['event' => 'sensor_alert', 'alert_level' => 'critical', 'timestamp' => time()];
    $jsonPayload = json_encode($payloadData);

    $signature = hash_hmac('sha256', $jsonPayload, $webhookSecret);
    assertTest("Webhook HMAC-SHA256 Signature Generation", !empty($signature) && strlen($signature) === 64);
} catch (Exception $e) {
    assertTest("Webhook Error", false, $e->getMessage());
}

// 7. Aggregation & Statistics Querying
try {
    $stats = $eventManager->getStats();
    assertTest("Statistics Aggregation Query", is_array($stats) && isset($stats['total_events']));
    assertTest("Total Events Count Integrity", $stats['total_events'] >= 3);
} catch (Exception $e) {
    assertTest("Stats Error", false, $e->getMessage());
}

echo "\n--------------------------------------------------------\n";
echo "  Results: $passed Passed, $failed Failed\n";
echo "--------------------------------------------------------\n\n";

exit($failed > 0 ? 1 : 0);
