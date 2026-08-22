<?php

require_once __DIR__ . '/../src/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();
$driver = $db->getDriver();

echo "Initializing Database (Driver: " . strtoupper($driver) . ")...\n";

if ($driver === 'pgsql') {
    // PostgreSQL / PostGIS schema
    $commands = [
        "CREATE TABLE IF NOT EXISTS events (
            id SERIAL PRIMARY KEY,
            event_id VARCHAR(100) UNIQUE NOT NULL,
            source_id VARCHAR(100) NOT NULL,
            type VARCHAR(100) NOT NULL,
            lat DOUBLE PRECISION NOT NULL,
            lon DOUBLE PRECISION NOT NULL,
            timestamp BIGINT NOT NULL,
            payload TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS sources (
            id SERIAL PRIMARY KEY,
            source_id VARCHAR(100) UNIQUE NOT NULL,
            source_name VARCHAR(255) NOT NULL,
            source_secret VARCHAR(255) NOT NULL,
            status VARCHAR(50) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS logs (
            id SERIAL PRIMARY KEY,
            source_id VARCHAR(100),
            ip_address VARCHAR(45),
            request_body TEXT,
            error_message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS outbound_queue (
            id SERIAL PRIMARY KEY,
            event_id VARCHAR(100),
            target_url TEXT,
            attempt_count INT DEFAULT 0,
            last_error TEXT,
            status VARCHAR(50) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS rate_limits (
            key_identifier VARCHAR(150) PRIMARY KEY,
            tokens DOUBLE PRECISION NOT NULL,
            last_updated DOUBLE PRECISION NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE INDEX IF NOT EXISTS idx_events_ts ON events (timestamp DESC)",
        "CREATE INDEX IF NOT EXISTS idx_events_type ON events (type)",
        "CREATE INDEX IF NOT EXISTS idx_events_source ON events (source_id)",
        "CREATE INDEX IF NOT EXISTS idx_events_coords ON events (lat, lon)"
    ];
} elseif ($driver === 'mysql') {
    // MySQL / MariaDB schema
    $commands = [
        "CREATE TABLE IF NOT EXISTS events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id VARCHAR(100) UNIQUE NOT NULL,
            source_id VARCHAR(100) NOT NULL,
            type VARCHAR(100) NOT NULL,
            lat DOUBLE NOT NULL,
            lon DOUBLE NOT NULL,
            timestamp BIGINT NOT NULL,
            payload LONGTEXT,
            ip_address VARCHAR(45),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ts (timestamp),
            INDEX idx_type (type),
            INDEX idx_source (source_id),
            INDEX idx_coords (lat, lon)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS sources (
            id INT AUTO_INCREMENT PRIMARY KEY,
            source_id VARCHAR(100) UNIQUE NOT NULL,
            source_name VARCHAR(255) NOT NULL,
            source_secret VARCHAR(255) NOT NULL,
            status VARCHAR(50) DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            source_id VARCHAR(100),
            ip_address VARCHAR(45),
            request_body LONGTEXT,
            error_message TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS outbound_queue (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id VARCHAR(100),
            target_url TEXT,
            attempt_count INT DEFAULT 0,
            last_error TEXT,
            status VARCHAR(50) DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS rate_limits (
            key_identifier VARCHAR(150) PRIMARY KEY,
            tokens DOUBLE NOT NULL,
            last_updated DOUBLE NOT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];
} else {
    // SQLite schema
    $commands = [
        "CREATE TABLE IF NOT EXISTS events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_id TEXT UNIQUE,
            source_id TEXT,
            type TEXT,
            lat REAL,
            lon REAL,
            timestamp INTEGER,
            payload TEXT,
            ip_address TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS sources (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            source_id TEXT UNIQUE,
            source_name TEXT,
            source_secret TEXT,
            status TEXT DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            source_id TEXT,
            ip_address TEXT,
            request_body TEXT,
            error_message TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS outbound_queue (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_id TEXT,
            target_url TEXT,
            attempt_count INTEGER DEFAULT 0,
            last_error TEXT,
            status TEXT DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS rate_limits (
            key_identifier TEXT PRIMARY KEY,
            tokens REAL NOT NULL,
            last_updated REAL NOT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE INDEX IF NOT EXISTS idx_events_ts ON events(timestamp DESC)",
        "CREATE INDEX IF NOT EXISTS idx_events_coords ON events(lat, lon)",
        "CREATE INDEX IF NOT EXISTS idx_events_type ON events(type)",
        "CREATE INDEX IF NOT EXISTS idx_events_source ON events(source_id)"
    ];
}

foreach ($commands as $cmd) {
    $pdo->exec($cmd);
}

// Seed default Test Source
$stmt = $pdo->prepare("SELECT COUNT(*) FROM sources WHERE source_id = 'test_source'");
$stmt->execute();
if ($stmt->fetchColumn() == 0) {
    $ins = $pdo->prepare("INSERT INTO sources (source_id, source_name, source_secret, status) VALUES ('test_source', 'Test Source', 'test_key', 'active')");
    $ins->execute();
    echo "Seeded default test source.\n";
}

echo "Database initialization completed successfully.\n";
