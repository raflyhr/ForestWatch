<?php

return [
    'spatial_radius_meters' => env('SPATIAL_RADIUS_METERS', 1500),
    'bounding_box' => env('NASA_BOUNDING_BOX', '95,-11,141,6'), // Indonesia approx
    'regions' => [
        'sumatra_north' => '95,0,106,6',
        'sumatra_south' => '95,-6,106,0',
        'jawa_bali' => '105,-9,116,-5',
        'kalimantan_nw' => '108,0,114,5',
        'kalimantan_ne' => '114,0,119,5',
        'kalimantan_sw' => '108,-5,114,0',
        'kalimantan_se' => '114,-5,119,0',
        'sulawesi' => '118,-6,126,2',
        'maluku' => '124,-5,132,2',
        'papua' => '132,-9,141,1',
    ],
    'weights' => [
        'nasa_hotspot' => env('WEIGHT_NASA_HOTSPOT', 40),
        'per_report' => env('WEIGHT_PER_REPORT', 20),
        'ai_evidence' => env('WEIGHT_AI_EVIDENCE', 15),
    ],
    'thresholds' => [
        'medium' => env('WARNING_THRESHOLD_MEDIUM', 30),
        'high' => env('WARNING_THRESHOLD_HIGH', 55),
        'critical' => env('WARNING_THRESHOLD_CRITICAL', 80),
    ],
];
