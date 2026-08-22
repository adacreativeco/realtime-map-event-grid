<?php

require_once __DIR__ . '/Database.php';

class Webhook {
    public static function enqueue($eventId) {
        $settings = require __DIR__ . '/../config/settings.php';
        if (!$settings['webhook_enabled'] || empty($settings['webhook_url'])) {
            return false;
        }
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("INSERT INTO outbound_queue (event_id, target_url, status, created_at, updated_at) VALUES (:eid, :url, 'pending', datetime('now'), datetime('now'))");
        return $stmt->execute([':eid' => $eventId, ':url' => $settings['webhook_url']]);
    }

    public static function dispatchPending() {
        $settings = require __DIR__ . '/../config/settings.php';
        $secret = $settings['webhook_secret'] ?? '';
        $pdo = Database::getInstance()->getConnection();
        $rows = $pdo->query("SELECT * FROM outbound_queue WHERE status = 'pending' ORDER BY id ASC LIMIT 50")->fetchAll();
        foreach ($rows as $row) {
            $eventStmt = $pdo->prepare("SELECT event_id, source_id, type, lat, lon, timestamp, payload, created_at FROM events WHERE event_id = :eid");
            $eventStmt->execute([':eid' => $row['event_id']]);
            $event = $eventStmt->fetch();
            if (!$event) {
                self::markFailed($pdo, $row['id'], 'Event not found');
                continue;
            }
            $body = [
                'event_id' => $event['event_id'],
                'source_id' => $event['source_id'],
                'type' => $event['type'],
                'lat' => (float)$event['lat'],
                'lon' => (float)$event['lon'],
                'timestamp' => (int)$event['timestamp'],
                'payload' => json_decode($event['payload'], true),
                'created_at' => $event['created_at']
            ];
            $json = json_encode($body);
            $sig = !empty($secret) ? hash_hmac('sha256', $json, $secret) : '';
            $ok = self::postJson($row['target_url'], $json, $sig);
            if ($ok) {
                self::markSent($pdo, $row['id']);
            } else {
                self::markRetry($pdo, $row['id'], 'Dispatch failed');
            }
        }
    }

    private static function postJson($url, $json, $signature) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        $headers = ['Content-Type: application/json'];
        if (!empty($signature)) {
            $headers[] = 'X-Signature: ' . $signature;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code >= 200 && $code < 300;
    }

    private static function markSent($pdo, $id) {
        $stmt = $pdo->prepare("UPDATE outbound_queue SET status='sent', updated_at=datetime('now') WHERE id=:id");
        $stmt->execute([':id' => $id]);
    }

    private static function markRetry($pdo, $id, $err) {
        $stmt = $pdo->prepare("UPDATE outbound_queue SET attempt_count = attempt_count + 1, last_error = :e, updated_at=datetime('now') WHERE id=:id");
        $stmt->execute([':id' => $id, ':e' => $err]);
    }

    private static function markFailed($pdo, $id, $err) {
        $stmt = $pdo->prepare("UPDATE outbound_queue SET status='failed', last_error = :e, updated_at=datetime('now') WHERE id=:id");
        $stmt->execute([':id' => $id, ':e' => $err]);
    }
}

