<?php

require_once __DIR__ . '/../../../src/EventManager.php';
require_once __DIR__ . '/../../../src/Auth.php';

header('Content-Type: application/json');

Auth::checkApi();

$eventManager = new EventManager();

try {
    $stats = $eventManager->getStats();
    echo json_encode(['status' => 'success', 'data' => $stats]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
