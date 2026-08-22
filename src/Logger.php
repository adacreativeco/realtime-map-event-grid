<?php

require_once __DIR__ . '/Database.php';

class Logger {
    public static function log($sourceId, $requestBody, $errorMessage, $ipAddress = null) {
        $pdo = Database::getInstance()->getConnection();
        
        if ($ipAddress === null) {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }

        $stmt = $pdo->prepare("
            INSERT INTO logs (source_id, ip_address, request_body, error_message, created_at)
            VALUES (:source_id, :ip_address, :request_body, :error_message, datetime('now'))
        ");

        $stmt->execute([
            ':source_id' => $sourceId,
            ':ip_address' => $ipAddress,
            ':request_body' => is_string($requestBody) ? $requestBody : json_encode($requestBody),
            ':error_message' => $errorMessage
        ]);
    }
}
