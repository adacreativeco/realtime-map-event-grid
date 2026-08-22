<?php

require_once __DIR__ . '/../../../../src/EventManager.php';
require_once __DIR__ . '/../../../../src/Database.php';

$settings = require __DIR__ . '/../../../../config/settings.php';

if (!$settings['public_read_api']) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Not Found']);
    exit;
}

header('Content-Type: application/json');
if (!empty($settings['public_cors_origin'])) {
    header('Access-Control-Allow-Origin: ' . $settings['public_cors_origin']);
}
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Optional API key enforcement
$providedKey = null;
if (function_exists('getallheaders')) {
    $headers = getallheaders();
    $providedKey = $headers['X-API-Key'] ?? $headers['x-api-key'] ?? null;
}
if (!$providedKey) {
    $providedKey = $_GET['api_key'] ?? null;
}
if (!empty($settings['public_read_require_api_key'])) {
    $expected = $settings['public_read_api_key'] ?? '';
    if (empty($expected) || $providedKey !== $expected) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
}

$eventManager = new EventManager();

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$maxLimit = (int)($settings['public_read_limit_max'] ?? 100);
if ($limit > $maxLimit) { $limit = $maxLimit; }
if ($limit <= 0) { $limit = 50; }

$filters = [
    'type' => $_GET['type'] ?? null,
    'source_id' => $_GET['source_id'] ?? null,
    'start_time' => $_GET['start_time'] ?? null,
    'end_time' => $_GET['end_time'] ?? null,
    'limit' => $limit,
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

    foreach ($events as &$event) {
        if (!$settings['public_read_allow_payload']) {
            unset($event['payload']);
            unset($event['ip_address']);
        } else {
            $event['payload'] = is_string($event['payload']) ? json_decode($event['payload'], true) : $event['payload'];
        }
    }

    echo json_encode(['status' => 'success', 'data' => $events, 'count' => count($events)]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
