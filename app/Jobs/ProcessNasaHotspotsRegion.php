<?php

namespace App\Jobs;

use App\Models\Hotspot;
use App\Services\IncidentEngine;
use App\Services\NasaFirmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class ProcessNasaHotspotsRegion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    public function __construct(public string $regionName, public string $boundingBox) {}

    public function handle(NasaFirmsService $nasa, IncidentEngine $engine): void
    {
        $data = $nasa->fetchActiveFires($this->boundingBox);
        if (empty($data)) {
            return;
        }

        $now = now();
        $hotspotsData = [];

        foreach ($data as $row) {
            $time = str_pad((string) ($row['acq_time'] ?? '0000'), 4, '0', STR_PAD_LEFT);
            $detectedAt = ($row['acq_date'] ?? now()->toDateString()).' '.substr($time, 0, 2).':'.substr($time, 2, 2).':00';

            $hotspotsData[] = [
                'latitude' => $row['latitude'],
                'longitude' => $row['longitude'],
                'detected_at' => Carbon::parse($detectedAt)->format('Y-m-d H:i:s'),
                'source' => 'NASA_FIRMS',
                'satellite' => $row['satellite'] ?? null,
                'confidence' => $row['confidence'] ?? null,
                'frp' => isset($row['frp']) ? (float) $row['frp'] : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $chunks = array_chunk($hotspotsData, 500);

        foreach ($chunks as $chunk) {
            Hotspot::upsert(
                $chunk,
                ['latitude', 'longitude', 'detected_at'],
                ['satellite', 'confidence', 'frp', 'updated_at']
            );
        }

        // Dispatch async job to link hotspots to incidents and recalculate warnings
        LinkHotspotsToIncidentsJob::dispatch($now->format('Y-m-d H:i:s'));
    }
}
