<?php

return [
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
