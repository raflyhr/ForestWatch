<?php

namespace App\Services;

use App\Models\Incident;

class SpatialMatchingService
{
    private int $radiusMeters;

    public function __construct(?int $radiusMeters = null)
    {
        $this->radiusMeters = $radiusMeters ?? config('forestwatch.spatial_radius_meters', 1500);
    }

    public function findNearbyIncident(float $lat, float $lng): ?Incident
    {
        $radiusKm = $this->radiusMeters / 1000;
        $latDistance = $radiusKm / 111.045;
        $lngDistance = $radiusKm / (111.045 * max(cos(deg2rad($lat)), 0.01));

        $candidates = Incident::query()
            ->whereBetween('latitude', [$lat - $latDistance, $lat + $latDistance])
            ->whereBetween('longitude', [$lng - $lngDistance, $lng + $lngDistance])
            ->whereNotIn('status', ['closed', 'false_alarm'])
            ->get();

        return $candidates
            ->map(function (Incident $incident) use ($lat, $lng) {
                $lat1 = deg2rad($lat);
                $lat2 = deg2rad((float) $incident->latitude);
                $deltaLat = $lat2 - $lat1;
                $deltaLng = deg2rad((float) $incident->longitude - $lng);
                $a = sin($deltaLat / 2) ** 2
                    + cos($lat1) * cos($lat2) * sin($deltaLng / 2) ** 2;

                return [
                    'incident' => $incident,
                    'distance_km' => 6371 * 2 * asin(min(1, sqrt($a))),
                ];
            })
            ->filter(fn (array $candidate) => $candidate['distance_km'] <= $radiusKm)
            ->sortBy('distance_km')
            ->first()['incident'] ?? null;
    }
}
