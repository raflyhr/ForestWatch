<?php

return [
    'spatial_radius_meters' => env('SPATIAL_RADIUS_METERS', 1500),
    'bounding_box' => env('NASA_BOUNDING_BOX', '95,-11,141,6'), // Indonesia approx
    'weights' => [
        'nasa_hotspot' => env('WEIGHT_NASA_HOTSPOT', 40),
        'per_report' => env('WEIGHT_PER_REPORT', 20),
        'ai_evidence' => env('WEIGHT_AI_EVIDENCE', 15),
    ],
];