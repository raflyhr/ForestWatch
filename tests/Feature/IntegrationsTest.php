<?php

namespace Tests\Feature;

use App\Services\ForestWatchApiService;
use App\Services\RoutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IntegrationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_forestwatch_microservice_fetches_water_sources(): void
    {
        config(['forestwatch.water_api_url' => 'https://forestwatch.railway.app/api/water']);

        Http::fake([
            'forestwatch.railway.app/*' => Http::response([
                'status' => 'success',
                'land_classification' => ['terrain_type' => 'Lahan Mineral'],
                'water_sources' => [['id' => 'FW-001']],
            ], 200),
        ]);

        $service = new ForestWatchApiService;
        $data = $service->findWaterSources(-6.2, 106.8);

        $this->assertEquals('success', $data['status']);
        $this->assertCount(1, $data['water_sources']);
    }

    public function test_routing_service_fetches_route(): void
    {
        Http::fake([
            'router.project-osrm.org/*' => Http::response([
                'code' => 'Ok',
                'routes' => [],
            ], 200),
        ]);

        $service = new RoutingService;
        $data = $service->getRoute(-6.2, 106.8, -6.3, 106.9);

        $this->assertEquals('Ok', $data['code']);
    }
}
