<?php

namespace App\Observers;

use App\Models\Incident;
use App\Models\WeatherSnapshot;
use App\Services\BmkgService;
use Illuminate\Support\Facades\Log;

class IncidentObserver
{
    public function __construct(
        private BmkgService $bmkg
    ) {}

    public function created(Incident $incident): void
    {
        $this->fetchAndSaveWeather($incident);
    }

    public function updated(Incident $incident): void
    {
        if ($incident->wasChanged(['latitude', 'longitude'])) {
            $this->fetchAndSaveWeather($incident);
        }
    }

    private function fetchAndSaveWeather(Incident $incident): void
    {
        try {
            $nearest = $this->bmkg->findNearestRegion($incident->latitude, $incident->longitude);

            if (!$nearest) {
                Log::warning("BMKG Observer: No region found for Incident ID {$incident->id} at ({$incident->latitude}, {$incident->longitude})");
                return;
            }

            $weather = $this->bmkg->fetchByAreaCode($nearest->area_code, $incident->latitude, $incident->longitude);

            if (empty($weather)) {
                Log::warning("BMKG Observer: No weather data for area {$nearest->area_code} (Incident ID {$incident->id})");
                return;
            }

            WeatherSnapshot::updateOrCreate(
                ['incident_id' => $incident->id],
                [
                    'temperature' => $weather['temperature'],
                    'humidity' => $weather['humidity'],
                    'wind_speed' => $weather['wind_speed'],
                    'wind_direction' => $weather['wind_direction'],
                    'recorded_at' => $weather['recorded_at'] ?? now(),
                ]
            );

            Log::info("BMKG Observer: Weather saved for Incident ID {$incident->id} via area {$nearest->area_code}");
        } catch (\Throwable $e) {
            Log::error("BMKG Observer: Failed for Incident ID {$incident->id}", ['error' => $e->getMessage()]);
        }
    }
}