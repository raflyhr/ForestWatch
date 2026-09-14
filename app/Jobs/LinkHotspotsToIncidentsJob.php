<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\IncidentEngine;
use App\Models\Hotspot;
use Illuminate\Support\Carbon;

class LinkHotspotsToIncidentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800; // 30 minutes

    public function __construct(public string $startTime)
    {
    }

    public function handle(IncidentEngine $engine): void
    {
        // Process hotspots updated since the start time in larger chunks
        Hotspot::where('source', 'NASA_FIRMS')
            ->where('updated_at', '>=', $this->startTime)
            ->whereNull('incident_id')
            ->chunkById(1000, function ($hotspots) use ($engine) {
                $engine->handleHotspots($hotspots);
            });
    }
}
