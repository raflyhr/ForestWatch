<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hotspot;
use App\Models\WaterSource;
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
        $hotspotLatitude = (float) $hotspot->latitude;
        $hotspotLongitude = (float) $hotspot->longitude;
        $sources = WaterSource::query()->where('is_verified', true)->get()->map(function (WaterSource $source) use ($hotspotLatitude, $hotspotLongitude) {
            $latDelta = deg2rad((float) $source->latitude - $hotspotLatitude);
            $lngDelta = deg2rad((float) $source->longitude - $hotspotLongitude);
            $a = sin($latDelta / 2) ** 2 + cos(deg2rad($hotspotLatitude)) * cos(deg2rad((float) $source->latitude)) * sin($lngDelta / 2) ** 2;
            $source->distance_m = 6371000 * 2 * asin(min(1, sqrt($a)));
            return $source;
        })->sortBy('distance_m')->values();

        if ($sources->isEmpty()) {
            $data = $waterApi->findWaterSources($hotspotLatitude, $hotspotLongitude);
            $sources = collect($data['water_sources'] ?? [])->filter(function ($source) {
                return is_numeric($source['latitude'] ?? $source['lat'] ?? null)
                    && is_numeric($source['longitude'] ?? $source['lng'] ?? $source['lon'] ?? null);
            })->values();
        }

        if ($sources->isEmpty()) {
            return response()->json(['message' => 'Sumber air terdekat tidak ditemukan.'], 404);
        }

        $source = $sources->first();
        $sourceData = $source instanceof WaterSource ? $source->toArray() : $source;
        $latitude = (float) ($sourceData['latitude'] ?? $sourceData['lat']);
        $longitude = (float) ($sourceData['longitude'] ?? $sourceData['lng'] ?? $sourceData['lon']);
        $route = $routing->getRoute((float) $hotspot->latitude, (float) $hotspot->longitude, $latitude, $longitude);

        if (($route['code'] ?? null) !== 'Ok' || empty($route['routes'][0])) {
            return response()->json(['message' => 'Rute menuju sumber air tidak ditemukan.'], 404);
        }

        $routeData = $route['routes'][0];

        return response()->json([
            'source' => [
                'name' => $sourceData['name'] ?? $sourceData['type'] ?? 'Sumber air terdekat',
                'type' => $sourceData['type'] ?? 'Tidak tersedia',
                'source' => $sourceData['source'] ?? 'ForestWatch water API',
                'is_verified' => (bool) ($sourceData['is_verified'] ?? false),
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
