<?php

namespace App\Jobs;

use App\Models\Incident;
use App\Services\BmkgService;
use App\Services\WarningEngine;
use App\Console\Commands\FetchBmkgWeather;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchBmkgForIncidentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $incidentIds) {}

    public function handle(BmkgService $bmkg, WarningEngine $warningEngine): void
    {
        $incidents = Incident::whereIn('id', $this->incidentIds)->get();
        
        if ($incidents->isEmpty()) {
            return;
        }

        // 1. Fetch BMKG data synchronously for these specific incidents
        (new FetchBmkgWeather())->processIncidents($incidents, $bmkg);

        // 2. Recalculate warning levels immediately after weather data is in
        foreach ($incidents as $incident) {
            $warningEngine->recalculate($incident);
        }
    }
}