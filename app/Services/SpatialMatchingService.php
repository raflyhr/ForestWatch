<?php

namespace App\Services;

use App\Models\Incident;

class SpatialMatchingService
{
    private int $radiusMeters;

    public function __construct(int $radiusMeters = null) {
        $this->radiusMeters = $radiusMeters ?? config('forestwatch.spatial_radius_meters', 1500);
    }

    public function findNearbyIncident(float $lat, float $lng): ?Incident
    {
        return Incident::whereRaw(
            'ST_DWithin(location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
            [$lng, $lat, $this->radiusMeters]
        )->whereNotIn('status', ['closed', 'false_alarm'])
         ->first();
    }
}