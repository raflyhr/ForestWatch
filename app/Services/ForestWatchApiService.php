<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ForestWatchApiService
{
    private const TIMEOUT = 120;

    public function findWaterSources(float $latitude, float $longitude): ?array
    {
        $apiUrl = config('forestwatch.water_api_url');

        if (! $apiUrl) {
            Log::error('FORESTWATCH_FIND_WATER_API_URL not configured');

            return null;
        }

        try {
            $response = Http::timeout(self::TIMEOUT)
                ->post($apiUrl, [
                    'lat' => $latitude,
                    'lng' => $longitude,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);

            if ($response->failed()) {
                Log::error('ForestWatch microservice error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'lat' => $latitude,
                    'lng' => $longitude,
                ]);

                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('ForestWatch microservice exception', [
                'message' => $e->getMessage(),
                'lat' => $latitude,
                'lng' => $longitude,
            ]);

            return null;
        }
    }
}
