<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RoutingService
{
    public function getRoute(float $startLat, float $startLng, float $endLat, float $endLng): array
    {
        $osrmUrl = "https://router.project-osrm.org/route/v1/driving/{$startLng},{$startLat};{$endLng},{$endLat}?overview=full&geometries=geojson";

        try {
            $response = Http::timeout(10)->get($osrmUrl);

            if ($response->failed()) {
                return [];
            }

            return $response->json();
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }
}
