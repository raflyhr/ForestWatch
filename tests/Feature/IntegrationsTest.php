<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use App\Services\BmkgService;
use App\Services\OsmService;
use App\Services\RoutingService;
use App\Models\IntegrationStatus;

class IntegrationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_bmkg_service_fetches_weather(): void
    {
        Http::fake([
            'api.bmkg.go.id/*' => Http::response([
                ['area_code' => '501210', 'temperature' => 30.5, 'humidity' => 80, 'wind_speed' => 12, 'wind_direction' => 'NW', 'recorded_at' => '2026-09-14 10:00:00']
            ], 200),
        ]);

        $service = new BmkgService();
        $data = $service->fetchWeatherData('501210');

        $this->assertCount(1, $data);
        $this->assertEquals('501210', $data[0]['area_code']);
        $this->assertDatabaseHas('integration_statuses', [
            'source' => 'bmkg',
            'status' => 'healthy',
        ]);
    }

    public function test_osm_service_fetches_water_sources(): void
    {
        Http::fake([
            'overpass-api.de/*' => Http::response([
                'elements' => [
                    ['type' => 'node', 'id' => 123, 'lat' => -6.2, 'lon' => 106.8]
                ]
            ], 200),
        ]);

        $service = new OsmService();
        $data = $service->fetchWaterSources(-6.2, 106.8);

        $this->assertCount(1, $data);
    }

    public function test_routing_service_fetches_route(): void
    {
        Http::fake([
            'router.project-osrm.org/*' => Http::response([
                'code' => 'Ok',
                'routes' => []
            ], 200),
        ]);

        $service = new RoutingService();
        $data = $service->getRoute(-6.2, 106.8, -6.3, 106.9);

        $this->assertEquals('Ok', $data['code']);
    }
}
