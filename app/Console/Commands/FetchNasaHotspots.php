<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NasaFirmsService;
use App\Services\IncidentEngine;
use App\Models\Hotspot;
use Illuminate\Support\Carbon;

class FetchNasaHotspots extends Command
{
    protected $signature = 'forestwatch:fetch-nasa';
    protected $description = 'Fetch active fire hotspots from NASA FIRMS';

    public function handle(NasaFirmsService $nasa, IncidentEngine $engine)
    {
        if (! config('services.nasa.api_key')) {
            $this->error('NASA_FIRMS_API_KEY is not configured.');
            return self::FAILURE;
        }

        $data = $nasa->fetchActiveFires(config('forestwatch.bounding_box', '95,-11,141,6'));

        foreach ($data as $row) {
            $time = str_pad((string) ($row['acq_time'] ?? '0000'), 4, '0', STR_PAD_LEFT);
            $detectedAt = ($row['acq_date'] ?? now()->toDateString()) . ' ' . substr($time, 0, 2) . ':' . substr($time, 2, 2) . ':00';
            $hotspot = Hotspot::firstOrCreate([
                'latitude' => $row['latitude'],
                'longitude' => $row['longitude'],
                'detected_at' => Carbon::parse($detectedAt),
                'source' => 'NASA_FIRMS',
            ], [
                'satellite' => $row['satellite'] ?? null,
                'confidence' => $row['confidence'] ?? null,
                'frp' => $row['frp'] ?? null,
            ]);

            $engine->handleHotspot($hotspot);
        }

        $this->info(count($data) . ' hotspots processed.');
    }
}
