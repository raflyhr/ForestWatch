<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\NasaFirmsService;
use App\Services\IncidentEngine;
use App\Models\Hotspot;
use Illuminate\Support\Carbon;

class ProcessNasaHotspotsRegion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    public function __construct(public string $regionName, public string $boundingBox)
    {
    }

    public function handle(NasaFirmsService $nasa, IncidentEngine $engine): void
    {
        $data = $nasa->fetchActiveFires($this->boundingBox);

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
    }
}
