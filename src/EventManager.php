<?php

require_once __DIR__ . '/Database.php';

class EventManager {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function validateSource($key) {
        $stmt = $this->pdo->prepare("SELECT * FROM sources WHERE source_secret = :key AND status = 'active'");
        $stmt->execute([':key' => $key]);
        return $stmt->fetch();
    }

    public function createEvent($data, $sourceId) {
        // Validate required fields
        $required = ['type', 'lat', 'lon', 'timestamp', 'payload'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing field: $field");
            }
        }

        // Validate coordinates
        if (!is_numeric($data['lat']) || !is_numeric($data['lon'])) {
            throw new Exception("Invalid coordinates");
        }

        $lat = (float)$data['lat'];
        $lon = (float)$data['lon'];

        if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
            throw new Exception("Coordinates out of range (-90..90, -180..180)");
        }

        // Generate ID if not provided
        $eventId = $data['event_id'] ?? 'evt_' . date('Ymd') . '_' . bin2hex(random_bytes(4));
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $stmt = $this->pdo->prepare("
            INSERT INTO events (event_id, source_id, type, lat, lon, timestamp, payload, ip_address, created_at)
            VALUES (:event_id, :source_id, :type, :lat, :lon, :timestamp, :payload, :ip_address, datetime('now'))
        ");

        $stmt->execute([
            ':event_id' => $eventId,
            ':source_id' => $sourceId,
            ':type' => $data['type'],
            ':lat' => $lat,
            ':lon' => $lon,
            ':timestamp' => (int)$data['timestamp'],
            ':payload' => is_string($data['payload']) ? $data['payload'] : json_encode($data['payload']),
            ':ip_address' => $ip
        ]);

        return $eventId;
    }

    public function getEvents($filters = []) {
        $sql = "SELECT * FROM events WHERE 1=1";
        $params = [];

        if (!empty($filters['type'])) {
            $sql .= " AND type = :type";
            $params[':type'] = $filters['type'];
        }

        if (!empty($filters['source_id'])) {
            $sql .= " AND source_id = :source_id";
            $params[':source_id'] = $filters['source_id'];
        }

        if (!empty($filters['after_id'])) {
            $stmt = $this->pdo->prepare("SELECT id, created_at FROM events WHERE event_id = :eid");
            $stmt->execute([':eid' => $filters['after_id']]);
            $lastEvent = $stmt->fetch();
            
            if ($lastEvent) {
                $sql .= " AND (created_at > :last_created_at OR (created_at = :last_created_at2 AND id > :last_id))";
                $params[':last_created_at'] = $lastEvent['created_at'];
                $params[':last_created_at2'] = $lastEvent['created_at'];
                $params[':last_id'] = $lastEvent['id'];
            }
        }
        
        if (!empty($filters['start_time'])) {
            $sql .= " AND timestamp >= :start_time";
            $params[':start_time'] = (int)$filters['start_time'];
        }

        if (!empty($filters['end_time'])) {
            $sql .= " AND timestamp <= :end_time";
            $params[':end_time'] = (int)$filters['end_time'];
        }

        // Bounding box filter (north, south, east, west)
        if (isset($filters['north']) && isset($filters['south']) && isset($filters['east']) && isset($filters['west'])) {
            $sql .= " AND lat <= :north AND lat >= :south AND lon <= :east AND lon >= :west";
            $params[':north'] = (float)$filters['north'];
            $params[':south'] = (float)$filters['south'];
            $params[':east'] = (float)$filters['east'];
            $params[':west'] = (float)$filters['west'];
        }

        // Search term in payload, event_id, or source_id
        if (!empty($filters['search'])) {
            $sql .= " AND (payload LIKE :search OR event_id LIKE :search OR type LIKE :search OR source_id LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY created_at DESC, id DESC LIMIT :limit";
        $limit = isset($filters['limit']) ? (int)$filters['limit'] : 100;
        if ($limit <= 0) $limit = 100;
        if ($limit > 1000) $limit = 1000;

        if (isset($filters['offset']) && (int)$filters['offset'] > 0) {
            $sql .= " OFFSET :offset";
        }

        $stmt = $this->pdo->prepare($sql);
        
        foreach ($params as $key => $val) {
            if (is_int($val)) {
                $stmt->bindValue($key, $val, PDO::PARAM_INT);
            } elseif (is_float($val)) {
                $stmt->bindValue($key, $val, PDO::PARAM_STR);
            } else {
                $stmt->bindValue($key, $val, PDO::PARAM_STR);
            }
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        if (isset($filters['offset']) && (int)$filters['offset'] > 0) {
            $stmt->bindValue(':offset', (int)$filters['offset'], PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getEventById($eventId) {
        $stmt = $this->pdo->prepare("SELECT * FROM events WHERE event_id = :eid LIMIT 1");
        $stmt->execute([':eid' => $eventId]);
        return $stmt->fetch();
    }

    public function getLatestEventsAfterId($lastId, $limit = 50) {
        if (empty($lastId)) {
            $stmt = $this->pdo->prepare("SELECT * FROM events ORDER BY created_at DESC, id DESC LIMIT :lim");
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        }

        $stmt = $this->pdo->prepare("SELECT id, created_at FROM events WHERE event_id = :eid");
        $stmt->execute([':eid' => $lastId]);
        $row = $stmt->fetch();

        if (!$row) {
            return $this->getEvents(['limit' => $limit]);
        }

        $stmt = $this->pdo->prepare("
            SELECT * FROM events 
            WHERE created_at > :cat OR (created_at = :cat2 AND id > :id)
            ORDER BY created_at ASC, id ASC LIMIT :lim
        ");
        $stmt->bindValue(':cat', $row['created_at']);
        $stmt->bindValue(':cat2', $row['created_at']);
        $stmt->bindValue(':id', $row['id'], PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getSources() {
        $stmt = $this->pdo->query("
            SELECT s.*, 
            (SELECT COUNT(*) FROM events WHERE source_id = s.source_id) as total_events,
            (SELECT MAX(created_at) FROM events WHERE source_id = s.source_id) as last_event_at
            FROM sources s 
            ORDER BY s.created_at DESC
        ");
        return $stmt->fetchAll();
    }

    public function addSource($name, $secret = null) {
        $id = 'src_' . bin2hex(random_bytes(4));
        if (!$secret) {
            $secret = 'key_' . bin2hex(random_bytes(16));
        }
        $stmt = $this->pdo->prepare("INSERT INTO sources (source_id, source_name, source_secret, status, created_at) VALUES (:id, :name, :secret, 'active', datetime('now'))");
        $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':secret' => $secret
        ]);
        return ['source_id' => $id, 'source_secret' => $secret];
    }
    
    public function toggleSourceStatus($sourceId) {
        $stmt = $this->pdo->prepare("SELECT status FROM sources WHERE source_id = :id");
        $stmt->execute([':id' => $sourceId]);
        $status = $stmt->fetchColumn();
        if (!$status) return false;

        $newStatus = ($status === 'active') ? 'inactive' : 'active';
        $updateStmt = $this->pdo->prepare("UPDATE sources SET status = :st WHERE source_id = :id");
        return $updateStmt->execute([':st' => $newStatus, ':id' => $sourceId]);
    }

    public function deleteSource($sourceId) {
        $stmt = $this->pdo->prepare("DELETE FROM sources WHERE source_id = :id");
        return $stmt->execute([':id' => $sourceId]);
    }

    public function getStats() {
        $now = time();
        $stats = [];

        // Total count
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM events");
        $stats['total_events'] = (int)$stmt->fetchColumn();

        // Time based counts
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM events WHERE timestamp >= :t");
        
        $stmt->execute([':t' => $now - 300]);
        $stats['last_5_min'] = (int)$stmt->fetchColumn();

        $stmt->execute([':t' => $now - 3600]);
        $stats['last_1_hour'] = (int)$stmt->fetchColumn();

        $stmt->execute([':t' => $now - 86400]);
        $stats['last_24_hours'] = (int)$stmt->fetchColumn();

        // Active sources
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM sources WHERE status = 'active'");
        $stats['active_sources'] = (int)$stmt->fetchColumn();

        // Type based
        $stmt = $this->pdo->query("SELECT type, COUNT(*) as count FROM events GROUP BY type ORDER BY count DESC");
        $stats['by_type'] = $stmt->fetchAll();

        // Source based
        $stmt = $this->pdo->query("
            SELECT s.source_name, e.source_id, COUNT(*) as count 
            FROM events e 
            LEFT JOIN sources s ON e.source_id = s.source_id 
            GROUP BY e.source_id 
            ORDER BY count DESC 
            LIMIT 10
        ");
        $stats['top_sources'] = $stmt->fetchAll();

        // 24 Hour Hourly Trend
        $hourly = [];
        for ($i = 23; $i >= 0; $i--) {
            $startHour = $now - (($i + 1) * 3600);
            $endHour = $now - ($i * 3600);
            $label = date('H:00', $endHour);

            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM events WHERE timestamp >= :start AND timestamp < :end");
            $stmt->execute([':start' => $startHour, ':end' => $endHour]);
            $count = (int)$stmt->fetchColumn();

            $hourly[] = [
                'hour' => $label,
                'timestamp' => $endHour,
                'count' => $count
            ];
        }
        $stats['hourly_trend'] = $hourly;

        return $stats;
    }
}
