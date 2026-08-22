<?php

require_once __DIR__ . '/../../../src/EventManager.php';
require_once __DIR__ . '/../../../src/Auth.php';
require_once __DIR__ . '/../../../src/Webhook.php';

header('Content-Type: application/json');

Auth::checkApi();

$eventManager = new EventManager();
$count = isset($_GET['count']) ? min(20, max(1, (int)$_GET['count'])) : 1;

// Seed Istanbul hotspot coordinates
$hotspots = [
    ['name' => 'Karaköy', 'lat' => 41.0232, 'lon' => 28.9773],
    ['name' => 'Kadıköy', 'lat' => 40.9904, 'lon' => 29.0292],
    ['name' => 'Beşiktaş', 'lat' => 41.0428, 'lon' => 29.0077],
    ['name' => 'Levent', 'lat' => 41.0825, 'lon' => 29.0125],
    ['name' => 'Taksim', 'lat' => 41.0370, 'lon' => 28.9850],
    ['name' => 'Üsküdar', 'lat' => 41.0264, 'lon' => 29.0152],
    ['name' => 'Bakırköy', 'lat' => 40.9782, 'lon' => 28.8744],
    ['name' => 'Şişli', 'lat' => 41.0602, 'lon' => 28.9877],
    ['name' => 'Maslak', 'lat' => 41.1118, 'lon' => 29.0211],
    ['name' => 'Ataşehir', 'lat' => 40.9927, 'lon' => 29.1244],
    ['name' => 'Maltepe', 'lat' => 40.9247, 'lon' => 29.1311],
    ['name' => 'Sarıyer', 'lat' => 41.1664, 'lon' => 29.0502]
];

$eventTemplates = [
    [
        'type' => 'vehicle_movement',
        'generator' => function($spot) {
            $vNum = rand(10, 99) . '-' . chr(rand(65, 90)) . chr(rand(65, 90)) . '-' . rand(100, 999);
            return [
                'vehicle_id' => 'VEH-' . $vNum,
                'speed_kmh' => rand(20, 110),
                'heading_deg' => rand(0, 359),
                'fuel_level' => rand(15, 100) . '%',
                'zone' => $spot['name'],
                'status' => 'IN_TRANSIT'
            ];
        }
    ],
    [
        'type' => 'sensor_alert',
        'generator' => function($spot) {
            $params = ['CO2', 'Smoke_Density', 'Humidity', 'Vibration', 'Pressure'];
            $param = $params[array_rand($params)];
            return [
                'sensor_id' => 'SENS-' . rand(1000, 9999),
                'parameter' => $param,
                'value' => rand(400, 2500),
                'threshold' => 1000,
                'status' => 'WARNING',
                'location_label' => $spot['name'] . ' Facility'
            ];
        }
    ],
    [
        'type' => 'delivery_completed',
        'generator' => function($spot) {
            return [
                'order_id' => 'ORD-' . strtoupper(bin2hex(random_bytes(3))),
                'courier_name' => ['Can Y.', 'Deniz A.', 'Burak T.', 'Elif M.', 'Oğuz S.'][rand(0, 4)],
                'duration_min' => rand(12, 45),
                'destination_area' => $spot['name'],
                'rating' => rand(4, 5) . ' Stars'
            ];
        }
    ],
    [
        'type' => 'temperature_spike',
        'generator' => function($spot) {
            return [
                'box_id' => 'COLD-BOX-' . rand(10, 99),
                'current_temp_c' => round(rand(350, 480) / 10, 1),
                'optimal_temp_c' => 18.0,
                'alert_level' => 'CRITICAL',
                'location' => $spot['name']
            ];
        }
    ],
    [
        'type' => 'security_incident',
        'generator' => function($spot) {
            return [
                'alarm_id' => 'SEC-' . rand(100, 999),
                'event_desc' => ['Perimeter Breach Detected', 'Unscheduled Door Open', 'Motion In Restricted Zone'][rand(0, 2)],
                'camera_feed' => 'CAM-' . rand(1, 16),
                'confidence' => round(rand(85, 99) / 100, 2),
                'sector' => $spot['name']
            ];
        }
    ]
];

$sources = $eventManager->getSources();
$defaultSourceId = !empty($sources) ? $sources[0]['source_id'] : 'test_source';

$created = [];

for ($i = 0; $i < $count; $i++) {
    $spot = $hotspots[array_rand($hotspots)];
    // Slight random jitter around the spot (+/- ~500m)
    $latJitter = (rand(-100, 100) / 10000);
    $lonJitter = (rand(-100, 100) / 10000);

    $tmpl = $eventTemplates[array_rand($eventTemplates)];
    $payload = $tmpl['generator']($spot);

    $sourceId = !empty($sources) ? $sources[array_rand($sources)]['source_id'] : $defaultSourceId;

    $eventData = [
        'type' => $tmpl['type'],
        'lat' => round($spot['lat'] + $latJitter, 6),
        'lon' => round($spot['lon'] + $lonJitter, 6),
        'timestamp' => time(),
        'payload' => $payload
    ];

    try {
        $id = $eventManager->createEvent($eventData, $sourceId);
        Webhook::enqueue($id);
        $eventData['event_id'] = $id;
        $eventData['source_id'] = $sourceId;
        $created[] = $eventData;
    } catch (Exception $e) {
        // continue
    }
}

echo json_encode([
    'status' => 'success',
    'message' => count($created) . ' event(s) generated successfully',
    'count' => count($created),
    'events' => $created
]);
