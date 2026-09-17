<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BmkgService;
use App\Models\WeatherSnapshot;
use App\Models\Incident;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class FetchBmkgWeather extends Command
{
    protected $signature = 'forestwatch:fetch-bmkg';
    protected $description = 'Fetch weather data from BMKG using spatial clustering for active incidents';

    public function handle(BmkgService $bmkg)
    {
        $this->line('Querying active incidents (last 24h)...');
        $incidents = Incident::where('created_at', '>=', Carbon::now()->subDay())
            ->get(['id', 'latitude', 'longitude']);

        if ($incidents->isEmpty()) {
            $this->info('No active incidents found.');
            return self::SUCCESS;
        }

        $this->line("Mapping " . $incidents->count() . " incidents to nearest BMKG stations...");
        
        $clusters = [];
        foreach ($incidents as $incident) {
            $nearest = $bmkg->findNearestRegion($incident->latitude, $incident->longitude);
            if ($nearest) {
                $clusters[$nearest->area_code]['name'] = $nearest->name;
                $clusters[$nearest->area_code]['incidents'][] = $incident->id;
                $clusters[$nearest->area_code]['lats'][] = $incident->latitude;
                $clusters[$nearest->area_code]['lons'][] = $incident->longitude;
            } else {
                $this->warn("No station found for Incident #{$incident->id}");
            }
        }

        $this->info("Grouped " . $incidents->count() . " incidents into " . count($clusters) . " BMKG station clusters.");

        foreach ($clusters as $areaCode => $cluster) {
            $this->line("Fetching weather for cluster: {$cluster['name']} ({$areaCode})...");
            
            // Calculate representative coordinates (average)
            $avgLat = array_sum($cluster['lats']) / count($cluster['lats']);
            $avgLon = array_sum($cluster['lons']) / count($cluster['lons']);

            $weather = $bmkg->fetchByAreaCode($areaCode, $avgLat, $avgLon);

            if (empty($weather)) {
                $this->warn("Failed to fetch weather for cluster {$cluster['name']}");
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

            // Bulk insert for this cluster
            WeatherSnapshot::insert($snapshots);
            $this->info("Stored weather for " . count($snapshots) . " incidents in {$cluster['name']}.");

            // Rate limiting pause
            usleep(500000);
        }

        $this->info('Batch weather processing completed.');
        return self::SUCCESS;
    }
}