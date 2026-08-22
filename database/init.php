<?php

require_once __DIR__ . '/../src/Database.php';

echo "Initializing Database...\n";

$pdo = Database::getInstance()->getConnection();

// Create Tables
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
        created_at DATETIME
    )",
    "CREATE TABLE IF NOT EXISTS sources (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        source_id TEXT UNIQUE,
        source_name TEXT,
        source_secret TEXT,
        status TEXT,
        created_at DATETIME
    )",
    "CREATE TABLE IF NOT EXISTS logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        source_id TEXT,
        ip_address TEXT,
        request_body TEXT,
        error_message TEXT,
        created_at DATETIME
    )",
    "CREATE TABLE IF NOT EXISTS outbound_queue (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        event_id TEXT,
        target_url TEXT,
        attempt_count INTEGER DEFAULT 0,
        last_error TEXT,
        status TEXT DEFAULT 'pending',
        created_at DATETIME,
        updated_at DATETIME
    )",
    "CREATE TABLE IF NOT EXISTS rate_limits (
        key_identifier TEXT PRIMARY KEY,
        tokens REAL NOT NULL,
        last_updated REAL NOT NULL,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($commands as $cmd) {
    $pdo->exec($cmd);
}

// Seed Source
$stmt = $pdo->prepare("SELECT COUNT(*) FROM sources WHERE source_id = 'test_source'");
$stmt->execute();
if ($stmt->fetchColumn() == 0) {
    $stmt = $pdo->prepare("INSERT INTO sources (source_id, source_name, source_secret, status, created_at) VALUES ('test_source', 'Test Source', 'test_key', 'active', datetime('now'))");
    $stmt->execute();
    echo "Seeded test source.\n";
}

echo "Database initialized successfully.\n";
