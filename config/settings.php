<?php

return [
    // Database Configuration (Multi-Driver: 'sqlite', 'pgsql', 'mysql')
    'db_driver' => getenv('DB_DRIVER') ?: 'sqlite',
    'db_host' => getenv('DB_HOST') ?: '127.0.0.1',
    'db_port' => getenv('DB_PORT') ?: (getenv('DB_DRIVER') === 'pgsql' ? '5432' : '3306'),
    'db_name' => getenv('DB_NAME') ?: 'rteg_db',
    'db_user' => getenv('DB_USER') ?: 'root',
    'db_pass' => getenv('DB_PASS') ?: '',
    'db_sslmode' => getenv('DB_SSLMODE') ?: 'prefer',
    'db_charset' => 'utf8mb4',
    'db_path' => __DIR__ . '/../database/events.db', // Used when db_driver is sqlite

    // Public API Settings
    'public_read_api' => true,
    'public_read_limit_max' => 100,
    'public_read_allow_payload' => false,
    'public_cors_origin' => '*',
    'public_read_require_api_key' => false,
    'public_read_api_key' => '',
    
    // Rate Limiting (Token Bucket)
    'rate_limit_enabled' => true,
    'rate_limit_max_requests' => 120, // Max 120 requests per minute per source
    'rate_limit_window' => 60,        // 60 seconds window
    
    // Webhooks
    'webhook_enabled' => false,
    'webhook_url' => '',
    'webhook_secret' => ''
];
