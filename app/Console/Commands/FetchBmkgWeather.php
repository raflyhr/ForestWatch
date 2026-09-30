<?php

namespace App\Console\Commands;

use App\Models\Incident;
use App\Models\WeatherSnapshot;
use App\Services\BmkgService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class FetchBmkgWeather extends Command
{
    protected $signature = 'forestwatch:fetch-bmkg';

    protected $description = 'Fetch weather data from BMKG using spatial clustering for active incidents';

    public function handle(BmkgService $bmkg)
    {
        $this->logMessage('info', 'Starting BMKG weather fetch...');
        $incidents = Incident::whereNotIn('status', ['closed', 'false_alarm'])
            ->get(['id', 'latitude', 'longitude']);

        if ($incidents->isEmpty()) {
            $this->logMessage('info', 'No active incidents found in the last 24 hours.');
            return self::SUCCESS;
        }

        $this->logMessage('info', 'Found ' . $incidents->count() . ' incidents to process.');
        $this->processIncidents($incidents, $bmkg);

        $this->logMessage('info', 'Batch weather processing completed.');

        return self::SUCCESS;
    }

    public static function dispatchForIncidents($incidents): void
    {
        \App\Jobs\FetchBmkgForIncidentsJob::dispatch($incidents->pluck('id')->toArray());
    }

    public function processIncidents($incidents, BmkgService $bmkg): void
    {
        $this->logMessage('info', 'Clustering incidents by nearest BMKG region...');
        $clusters = [];
        foreach ($incidents as $incident) {
            $nearest = $bmkg->findNearestRegion($incident->latitude, $incident->longitude);
            if ($nearest) {
                $clusters[$nearest->area_code]['name'] = $nearest->name;
                $clusters[$nearest->area_code]['incidents'][] = $incident->id;
                $clusters[$nearest->area_code]['lats'][] = $incident->latitude;
                $clusters[$nearest->area_code]['lons'][] = $incident->longitude;
            }
        }

        $this->logMessage('info', 'Processing ' . count($clusters) . ' region clusters...');
        foreach ($clusters as $areaCode => $cluster) {
            $avgLat = array_sum($cluster['lats']) / count($cluster['lats']);
            $avgLon = array_sum($cluster['lons']) / count($cluster['lons']);

            $this->logMessage('info', "Fetching weather for area: {$cluster['name']} ({$areaCode})...");
            try {
                $weather = $bmkg->fetchByAreaCode($areaCode, $avgLat, $avgLon);

                if (empty($weather)) {
                    $this->logMessage('warn', "No weather data for {$areaCode}");
                    continue;
                }

                $snapshots = [];
                foreach ($cluster['incidents'] as $incidentId) {
                    $snapshots[] = [
                        'incident_id' => $incidentId,
                        'temperature' => $weather['temperature'],
                        'humidity' => $weather['humidity'],
                        'wind_speed' => $weather['wind_speed'],
                        'wind_direction' => $weather['wind_direction'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                $this->logMessage('info', 'Inserting ' . count($snapshots) . " snapshots for area {$areaCode}...");
                WeatherSnapshot::insert($snapshots);
            } catch (\Throwable $e) {
                $this->logMessage('error', "Error processing {$areaCode}: " . $e->getMessage());
                Log::error("BMKG Command: Failed for area {$areaCode}", ['error' => $e->getMessage()]);
            }

            usleep(500000); // 0.5s delay to be polite to BMKG API
        }
    }

    protected function logMessage(string $type, string $message): void
    {
        if ($this->output !== null) {
            $this->{$type}($message);
        } else {
            Log::{$type}($message);
        }
    }
}
