<?php

$url = 'http://localhost:8081/api/v1/event/ingest.php';
$apiKey = 'test_key';

$events = [
    [
        'type' => 'vehicle_movement',
        'lat' => 41.015137,
        'lon' => 28.979530,
        'payload' => ['speed' => 65, 'vehicle_id' => 'A45']
    ],
    [
        'type' => 'sensor_alert',
        'lat' => 41.0082,
        'lon' => 28.9784,
        'payload' => ['temp' => 85, 'unit' => 'C']
    ],
    [
        'type' => 'delivery_completed',
        'lat' => 41.0200,
        'lon' => 28.9900,
        'payload' => ['order_id' => '12345']
    ]
];

foreach ($events as $evt) {
    $data = [
        'type' => $evt['type'],
        'lat' => $evt['lat'],
        'lon' => $evt['lon'],
        'timestamp' => time(),
        'payload' => $evt['payload']
    ];

    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n" .
                         "X-Source-Key: $apiKey\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
            'ignore_errors' => true
        ]
    ];
    
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    // Get headers to check status
    $status_line = $http_response_header[0];
    
    echo "Sent {$evt['type']} -> Status: $status_line | Response: $result\n";
    sleep(1);
}
