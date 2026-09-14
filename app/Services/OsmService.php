<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OsmService
{
    public function fetchWaterSources(float $lat, float $lng, int $radius = 5000): array
    {
        $overpassUrl = 'https://overpass-api.de/api/interpreter';
        $query = "[out:json];
            (
              node[\"natural\"=\"water\"](around:{$radius},{$lat},{$lng});
              way[\"natural\"=\"water\"](around:{$radius},{$lat},{$lng});
              relation[\"natural\"=\"water\"](around:{$radius},{$lat},{$lng});
              node[\"amenity\"=\"fire_hydrant\"](around:{$radius},{$lat},{$lng});
            );
            out center;";

        try {
            $response = Http::timeout(30)->post($overpassUrl, ['data' => $query]);

            if ($response->failed()) {
                return [];
            }

            return $response->json()['elements'] ?? [];
        } catch (\Throwable $e) {
            report($e);
            return [];
        }
    }
}
