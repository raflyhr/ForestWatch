<?php

namespace App\Console\Commands;

use App\Models\Incident;
use App\Services\ForestWatchApiService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('forestwatch:test-gis-live {--lat= : Latitude} {--lng= : Longitude} {lat? : Latitude} {lng? : Longitude}')]
#[Description('Test GIS data live by fetching real coordinates from database or direct lat/lng via ForestWatch Microservice')]
class TestGisLive extends Command
{
    public function handle(ForestWatchApiService $forestWatchApi)
    {
        $lat = $this->option('lat') ?? $this->argument('lat');
        $lng = $this->option('lng') ?? $this->argument('lng');

        if ($lat !== null && $lng !== null) {
            $this->testSingleCoordinate($forestWatchApi, (float) $lat, (float) $lng);

            return;
        }

        $this->testFromDatabase($forestWatchApi);
    }

    private function testSingleCoordinate(ForestWatchApiService $forestWatchApi, float $lat, float $lng): void
    {
        $this->info('--------------------------------------------------');
        $this->info("Testing coordinates: {$lat}, {$lng}");

        try {
            $response = $forestWatchApi->findWaterSources($lat, $lng);

            $this->info('Microservice Response Result:');
            $this->line(json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());
        }

        $this->info('--------------------------------------------------');
    }

    private function testFromDatabase(ForestWatchApiService $forestWatchApi): void
    {
        $incidents = Incident::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($incidents->isEmpty()) {
            $this->error('No incidents found with coordinates in the database.');

            return;
        }

        $this->info("Found {$incidents->count()} latest incidents. Processing live microservice data...");

        foreach ($incidents as $index => $incident) {
            $this->info('--------------------------------------------------');
            $this->info("Incident ID: {$incident->id}");
            $this->info("Coordinates: {$incident->latitude}, {$incident->longitude}");

            try {
                $response = $forestWatchApi->findWaterSources($incident->latitude, $incident->longitude);

                if (! $response || ($response['status'] ?? '') !== 'success') {
                    $this->error('Failed to get valid response from microservice.');

                    continue;
                }

                $this->info('Microservice Data Found. Updating Incident record...');

                $incident->update([
                    'land_classification' => $response['land_classification'] ?? null,
                    'water_sources' => $response['water_sources'] ?? null,
                    'fire_propagation' => $response['fire_propagation'] ?? null,
                    'algorithms_active' => $response['algorithms_active'] ?? null,
                    'raw_microservice_payload' => $response,
                ]);

                $this->info('Database updated successfully with microservice payload.');
            } catch (\Exception $e) {
                $this->error("Error processing Incident #{$incident->id}: ".$e->getMessage());
            }

            if ($index < $incidents->count() - 1) {
                $this->info('Waiting for next request...');
                sleep(1);
            }
        }

        $this->info('--------------------------------------------------');
        $this->info('Test GIS Live completed.');
    }
}
