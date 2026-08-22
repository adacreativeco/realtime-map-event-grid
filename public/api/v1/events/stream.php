<?php

// Prevent execution timeout
set_time_limit(0);
ignore_user_abort(false);

require_once __DIR__ . '/../../../../src/EventManager.php';
require_once __DIR__ . '/../../../../src/Auth.php';

// Release session lock so client can make other requests concurrently
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// Set SSE Headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache, no-transform');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');
header('Access-Control-Allow-Origin: *');

// Disable output buffering
while (ob_get_level() > 0) {
    ob_end_flush();
}
ob_implicit_flush(1);

$eventManager = new EventManager();

// Read Last Event ID from query or header
$lastEventId = $_GET['last_event_id'] ?? $_SERVER['HTTP_LAST_EVENT_ID'] ?? null;
$filterType = $_GET['type'] ?? null;
$filterSource = $_GET['source_id'] ?? null;

// Send initial connected message
echo "event: connected\n";
echo "data: " . json_encode(['status' => 'connected', 'timestamp' => time()]) . "\n\n";
flush();

$startTime = time();
$maxExecutionTime = 55; // 55 seconds to prevent gateway timeouts

$lastPing = time();

while (!connection_aborted() && (time() - $startTime) < $maxExecutionTime) {
    try {
        $events = $eventManager->getLatestEventsAfterId($lastEventId, 20);

        if (!empty($events)) {
            foreach ($events as $event) {
                // Apply filter if specified
                if ($filterType && $event['type'] !== $filterType) {
                    $lastEventId = $event['event_id'];
                    continue;
                }
                if ($filterSource && $event['source_id'] !== $filterSource) {
                    $lastEventId = $event['event_id'];
                    continue;
                }

                $event['payload'] = is_string($event['payload']) ? json_decode($event['payload'], true) : $event['payload'];

                echo "id: {$event['event_id']}\n";
                echo "event: event\n";
                echo "data: " . json_encode($event) . "\n\n";
                flush();

                $lastEventId = $event['event_id'];
            }
        }
    } catch (Exception $e) {
        echo "event: error\n";
        echo "data: " . json_encode(['message' => $e->getMessage()]) . "\n\n";
        flush();
        break;
    }

    // Send heartbeat ping every 10 seconds
    if (time() - $lastPing >= 10) {
        echo "event: ping\n";
        echo "data: " . json_encode(['time' => time()]) . "\n\n";
        flush();
        $lastPing = time();
    }

    // Sleep 1 second
    usleep(1000000);
}

// Graceful reconnect signal before timeout
echo "event: reconnect\n";
echo "data: " . json_encode(['last_event_id' => $lastEventId]) . "\n\n";
flush();
