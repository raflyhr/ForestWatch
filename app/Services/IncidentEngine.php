<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\Hotspot;
use App\Models\Report;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class IncidentEngine
{
    public function __construct(
        private SpatialMatchingService $spatial,
        private WarningEngine $warningEngine,
    ) {}

    public function handleHotspot(Hotspot $hotspot): Incident
    {
        return $this->handleHotspots(collect([$hotspot]))->first();
    }

    /**
     * Bulk process hotspots to link them to incidents and recalculate warnings.
     * Uses PostGIS/SQL spatial queries for extreme speed, with PHP fallback.
     */
    public function handleHotspots(Collection $hotspots): Collection
    {
        if ($hotspots->isEmpty()) {
            return collect();
        }

        $hotspotIds = $hotspots->pluck('id')->filter()->values();
        if ($hotspotIds->isEmpty()) {
            return collect();
        }

        // 1. Try PostgreSQL/PostGIS bulk spatial update first
        if (DB::connection()->getDriverName() === 'pgsql') {
            try {
                $radiusMeters = config('forestwatch.spatial_radius_meters', 1500);
                $idList = $hotspotIds->implode(',');

                // Link unassigned hotspots to existing open incidents within radius using PostGIS
                DB::statement("
                    UPDATE hotspots h
                    SET incident_id = i.id,
                        updated_at = NOW()
                    FROM (
                        SELECT id, latitude, longitude
                        FROM incidents
                        WHERE status NOT IN ('closed', 'false_alarm')
                    ) i
                    WHERE h.id IN ({$idList})
                      AND h.incident_id IS NULL
                      AND ST_DWithin(
                          ST_SetSRID(ST_MakePoint(h.longitude, h.latitude), 4326)::geography,
                          ST_SetSRID(ST_MakePoint(i.longitude, i.latitude), 4326)::geography,
                          {$radiusMeters}
                      )
                ");

                // Process remaining unassigned hotspots by clustering them into new incidents
                $unassigned = Hotspot::whereIn('id', $hotspotIds)
                    ->whereNull('incident_id')
                    ->get();

                if ($unassigned->isNotEmpty()) {
                    $this->createIncidentsAndAssign($unassigned, $radiusMeters);
                }

                // Recalculate warnings for all affected incidents
                $affectedIncidentIds = Hotspot::whereIn('id', $hotspotIds)
                    ->whereNotNull('incident_id')
                    ->pluck('incident_id')
                    ->unique();

                foreach ($affectedIncidentIds->chunk(100) as $chunkIds) {
                    $incidents = Incident::whereIn('id', $chunkIds)->get();
                    foreach ($incidents as $incident) {
                        $this->warningEngine->recalculate($incident);
                    }
                }

                return Incident::whereIn('id', $affectedIncidentIds)->get();
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // 2. Fallback in-memory matching if PostGIS is unavailable or fails
        return $this->handleHotspotsInMemory($hotspots);
    }

    private function createIncidentsAndAssign(Collection $unassigned, float $radiusMeters): void
    {
        $radiusKm = $radiusMeters / 1000;
        $createdIncidents = collect();

        foreach ($unassigned as $hotspot) {
            $match = $this->findMatchInMemory($hotspot->latitude, $hotspot->longitude, $createdIncidents, $radiusKm);

            if (!$match) {
                $match = Incident::create([
                    'latitude' => $hotspot->latitude,
                    'longitude' => $hotspot->longitude,
                    'status' => 'unverified',
                ]);
                $createdIncidents->push($match);
            }

            $hotspot->update([
                'incident_id' => $match->id,
            ]);
        }
    }

    private function handleHotspotsInMemory(Collection $hotspots): Collection
    {
        $allAffectedIncidentIds = [];
        $radiusKm = config('forestwatch.spatial_radius_meters', 1500) / 1000;

        foreach ($hotspots->chunk(200) as $chunk) {
            DB::transaction(function () use ($chunk, &$allAffectedIncidentIds, $radiusKm) {
                $minLat = $chunk->min('latitude');
                $maxLat = $chunk->max('latitude');
                $minLng = $chunk->min('longitude');
                $maxLng = $chunk->max('longitude');

                $latBuffer = $radiusKm / 111.045;
                $safeLat = max(abs($minLat), abs($maxLat));
                $lngBuffer = $radiusKm / (111.045 * max(cos(deg2rad($safeLat)), 0.01));

                $candidates = Incident::query()
                    ->whereBetween('latitude', [$minLat - $latBuffer, $maxLat + $latBuffer])
                    ->whereBetween('longitude', [$minLng - $lngBuffer, $maxLng + $lngBuffer])
                    ->whereNotIn('status', ['closed', 'false_alarm'])
                    ->get();

                $newlyCreatedInChunk = collect();
                $hotspotUpdates = [];

                foreach ($chunk as $hotspot) {
                    $match = $this->findMatchInMemory($hotspot->latitude, $hotspot->longitude, $candidates, $radiusKm)
                        ?? $this->findMatchInMemory($hotspot->latitude, $hotspot->longitude, $newlyCreatedInChunk, $radiusKm);

                    if ($match) {
                        $incidentId = $match->id;
                    } else {
                        $newIncident = Incident::create([
                            'latitude' => $hotspot->latitude,
                            'longitude' => $hotspot->longitude,
                            'status' => 'unverified',
                        ]);
                        $newlyCreatedInChunk->push($newIncident);
                        $incidentId = $newIncident->id;
                    }

                    $hotspotUpdates[] = [
                        'id' => $hotspot->id,
                        'latitude' => $hotspot->latitude,
                        'longitude' => $hotspot->longitude,
                        'detected_at' => $hotspot->detected_at,
                        'incident_id' => $incidentId,
                        'updated_at' => now(),
                    ];
                    $allAffectedIncidentIds[] = $incidentId;
                }

                if (!empty($hotspotUpdates)) {
                    Hotspot::upsert($hotspotUpdates, ['id'], ['incident_id', 'updated_at']);
                }
            });
        }

        $uniqueIncidentIds = array_unique($allAffectedIncidentIds);
        
        foreach (array_chunk($uniqueIncidentIds, 50) as $idChunk) {
            $incidents = Incident::whereIn('id', $idChunk)->get();
            foreach ($incidents as $incident) {
                $this->warningEngine->recalculate($incident);
            }
        }

        return Incident::whereIn('id', $uniqueIncidentIds)->get();
    }

    private function findMatchInMemory(float $lat, float $lng, Collection $candidates, float $radiusKm): ?Incident
    {
        return $candidates
            ->map(function ($incident) use ($lat, $lng) {
                $lat1 = deg2rad($lat);
                $lat2 = deg2rad((float) $incident->latitude);
                $deltaLat = $lat2 - $lat1;
                $deltaLng = deg2rad((float) $incident->longitude - $lng);
                $a = sin($deltaLat / 2) ** 2
                    + cos($lat1) * cos($lat2) * sin($deltaLng / 2) ** 2;
                
                $dist = 6371 * 2 * asin(min(1, sqrt($a)));
                return ['incident' => $incident, 'dist' => $dist];
            })
            ->filter(fn($c) => $c['dist'] <= $radiusKm)
            ->sortBy('dist')
            ->first()['incident'] ?? null;
    }

    public function handleReport(Report $report): Incident
    {
        return DB::transaction(function () use ($report) {
            $incident = $this->spatial->findNearbyIncident($report->latitude, $report->longitude)
                ?? Incident::create([
                    'latitude' => $report->latitude,
                    'longitude' => $report->longitude,
                    'status' => 'unverified',
                ]);

            $report->update(['incident_id' => $incident->id]);
            $this->warningEngine->recalculate($incident);

            return $incident;
        });
    }
}
