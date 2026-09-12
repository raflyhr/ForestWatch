<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NasaFirmsService;
use App\Services\IncidentEngine;
use App\Models\Hotspot;

class FetchNasaHotspots extends Command
{
    protected $signature = 'forestwatch:fetch-nasa';
    protected $description = 'Fetch active fire hotspots from NASA FIRMS';

    public function handle(NasaFirmsService $nasa, IncidentEngine $engine)
    {
        $data = $nasa->fetchActiveFires(config('forestwatch.bounding_box', '95,-11,141,6'));

        foreach ($data as $row) {
            $hotspot = Hotspot::create([
                'latitude' => $row['latitude'],
                'longitude' => $row['longitude'],
                'detected_at' => $row['acq_date'] . ' ' . $row['acq_time'],
                'satellite' => $row['satellite'] ?? null,
                'confidence' => $row['confidence'] ?? null,
                'frp' => $row['frp'] ?? null,
            ]);

            $engine->handleHotspot($hotspot);
        }

        $this->info(count($data) . ' hotspots processed.');
    }
}