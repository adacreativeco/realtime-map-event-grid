<?php

require_once __DIR__ . '/../../../src/EventManager.php';
require_once __DIR__ . '/../../../src/Auth.php';

header('Content-Type: application/json');

// Check Authentication
Auth::checkApi();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$eventManager = new EventManager();
$filters = [
    'type' => $_GET['type'] ?? null,
    'source_id' => $_GET['source_id'] ?? null,
    'start_time' => $_GET['start_time'] ?? null,
    'end_time' => $_GET['end_time'] ?? null,
    'limit' => $_GET['limit'] ?? 100,
    'offset' => $_GET['offset'] ?? 0,
    'after_id' => $_GET['after_id'] ?? null,
    'search' => $_GET['search'] ?? null,
    'north' => $_GET['north'] ?? null,
    'south' => $_GET['south'] ?? null,
    'east' => $_GET['east'] ?? null,
    'west' => $_GET['west'] ?? null
];

try {
    $events = $eventManager->getEvents($filters);
    
    // Decode payload for response
    foreach ($events as &$event) {
        if (is_string($event['payload'])) {
            $event['payload'] = json_decode($event['payload'], true);
        }
    }
    
    echo json_encode(['status' => 'success', 'data' => $events, 'count' => count($events)]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
