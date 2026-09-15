<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\IntegrationStatus;
use App\Models\ActivityLog;

class BmkgService
{
    /**
     * Fetch weather data from BMKG using a specific area code.
     */
    public function fetchByAreaCode(string $areaCode): array
    {
        try {
            $baseUrl = config('services.bmkg.base_url', 'https://api.bmkg.go.id');
            
            $response = Http::timeout(10)
                ->retry(2, 200)
                ->get($baseUrl . '/publik/prakiraan-cuaca', [
                    'adm4' => $areaCode
                ]);

            if ($response->failed()) {
                Log::error("BMKG: API request failed for $areaCode. Status: " . $response->status());
                $this->failed("HTTP request failed: " . $response->status());
                return [];
            }

            $raw = $response->json();
            
            if (empty($raw) || !isset($raw['data'][0]['cuaca'][0])) {
                Log::warning("BMKG: No forecast data returned for area $areaCode");
                return [];
            }

            $forecast = $raw['data'][0]['cuaca'][0];
            
            IntegrationStatus::updateOrCreate(['source' => 'bmkg'], [
                'status' => 'healthy', 
                'last_success_at' => now(),
                'last_error' => null, 
                'last_record_count' => 1,
            ]);

            return [
                'temperature' => $forecast['t'] ?? null,
                'humidity' => $forecast['hu'] ?? null,
                'wind_speed' => $forecast['ws'] ?? null,
                'wind_direction' => $forecast['wd'] ?? null,
                'recorded_at' => $forecast['local_datetime'] ?? now()->toDateTimeString(),
            ];

        } catch (\Throwable $e) {
            Log::error("BMKG: Exception in fetchByAreaCode ($areaCode): " . $e->getMessage());
            $this->failed($e->getMessage());
            return [];
        }
    }

    /**
     * Finds the nearest BMKG region code for a given coordinate.
     */
    public function findNearestRegion(float $lat, float $long): ?object
    {
        return DB::table('bmkg_regions')
            ->select('area_code', 'name')
            ->selectRaw('(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance', [$lat, $long, $lat])
            ->orderBy('distance')
            ->first();
    }

    private function failed(string $message): void
    {
        try {
            $status = IntegrationStatus::updateOrCreate(['source' => 'bmkg'], [
                'status' => 'failed', 
                'last_failure_at' => now(), 
                'last_error' => $message,
            ]);

            ActivityLog::create([
                'action' => 'integration.failed', 
                'target_type' => IntegrationStatus::class,
                'target_id' => $status->id, 
                'new_values' => ['source' => 'bmkg', 'error' => $message],
            ]);
        } catch (\Throwable $ignore) {}
    }
}