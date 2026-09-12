<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\Hotspot;
use App\Models\Report;

class IncidentEngine
{
    public function __construct(
        private SpatialMatchingService $spatial,
        private WarningEngine $warningEngine,
    ) {}

    public function handleHotspot(Hotspot $hotspot): Incident
    {
        $incident = $this->spatial->findNearbyIncident($hotspot->latitude, $hotspot->longitude)
            ?? Incident::create([
                'latitude' => $hotspot->latitude,
                'longitude' => $hotspot->longitude,
                'status' => 'unverified',
            ]);

        $hotspot->update(['incident_id' => $incident->id]);
        $this->warningEngine->recalculate($incident);

        return $incident;
    }

    public function handleReport(Report $report): Incident
    {
        $incident = $this->spatial->findNearbyIncident($report->latitude, $report->longitude)
            ?? Incident::create([
                'latitude' => $report->latitude,
                'longitude' => $report->longitude,
                'status' => 'unverified',
            ]);

        $report->update(['incident_id' => $incident->id]);
        $this->warningEngine->recalculate($incident);

        return $incident;
    }
}