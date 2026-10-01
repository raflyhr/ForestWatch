<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hotspot;
use App\Services\ForestWatchApiService;
use App\Services\RoutingService;
use Illuminate\Http\Request;

class HotspotController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->query('per_page', 25), 1), 100);

        return Hotspot::latest('detected_at')->paginate($perPage);
    }

    public function waterRoute(Hotspot $hotspot, ForestWatchApiService $waterApi, RoutingService $routing)
    {
        $data = $waterApi->findWaterSources((float) $hotspot->latitude, (float) $hotspot->longitude);
        $sources = collect($data['water_sources'] ?? [])->filter(function ($source) {
            return is_numeric($source['latitude'] ?? $source['lat'] ?? null)
                && is_numeric($source['longitude'] ?? $source['lng'] ?? $source['lon'] ?? null);
        })->values();

        if ($sources->isEmpty()) {
            return response()->json(['message' => 'Sumber air terdekat tidak ditemukan.'], 404);
        }

        $source = $sources->first();
        $latitude = (float) ($source['latitude'] ?? $source['lat']);
        $longitude = (float) ($source['longitude'] ?? $source['lng'] ?? $source['lon']);
        $route = $routing->getRoute((float) $hotspot->latitude, (float) $hotspot->longitude, $latitude, $longitude);

        if (($route['code'] ?? null) !== 'Ok' || empty($route['routes'][0])) {
            return response()->json(['message' => 'Rute menuju sumber air tidak ditemukan.'], 404);
        }

        $routeData = $route['routes'][0];

        return response()->json([
            'source' => [
                'name' => $source['name'] ?? $source['type'] ?? 'Sumber air terdekat',
                'type' => $source['type'] ?? 'Tidak tersedia',
                'latitude' => $latitude,
                'longitude' => $longitude,
            ],
            'route' => [
                'distance_m' => $routeData['distance'] ?? null,
                'duration_s' => $routeData['duration'] ?? null,
                'geometry' => $routeData['geometry'] ?? null,
            ],
        ]);
    }
}
