<?php

require_once __DIR__ . '/../../../../src/EventManager.php';
require_once __DIR__ . '/../../../../src/Logger.php';
require_once __DIR__ . '/../../../../src/Webhook.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Get headers
$headers = getallheaders();
$sourceKey = $headers['X-Source-Key'] ?? $headers['x-source-key'] ?? null;

if (!$sourceKey) {
    Logger::log('unknown', file_get_contents('php://input'), 'Missing X-Source-Key');
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Missing X-Source-Key']);
    exit;
}

$eventManager = new EventManager();
$source = $eventManager->validateSource($sourceKey);

if (!$source) {
    Logger::log('unknown', file_get_contents('php://input'), 'Invalid Source Key: ' . $sourceKey);
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid API Key']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    Logger::log($source['source_id'], $input, 'Invalid JSON');
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

try {
    $eventId = $eventManager->createEvent($data, $source['source_id']);
    Webhook::enqueue($eventId);
    http_response_code(201);
    echo json_encode(['status' => 'success', 'event_id' => $eventId]);
} catch (Exception $e) {
    Logger::log($source['source_id'], $input, $e->getMessage());
    // Determine error code based on message
    if (strpos($e->getMessage(), 'Missing field') !== false) {
        http_response_code(400);
    } elseif (strpos($e->getMessage(), 'Invalid coordinates') !== false) {
        http_response_code(422);
    } else {
        http_response_code(500);
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
