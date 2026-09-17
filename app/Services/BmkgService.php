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
     * Fetch weather data from BMKG using a dual fallback mechanism (ADM4 -> ADM2) 
     * with OpenWeatherMap coordinate-based fallback.
     */
    public function fetchByAreaCode(string $areaCode, ?float $lat = null, ?float $lon = null): array
    {
        $baseUrl = config('services.bmkg.base_url', 'https://api.bmkg.go.id');

        // Kamus override darurat untuk wilayah yang endpoint utamanya ditolak server BMKG
        $overrideMap = [
            '61.71.01.1001' => '61.71', // Pontianak
            '14.71.01.1001' => '14.71', // Pekanbaru
            '18.71.01.1001' => '18.71', // Bandar Lampung
            '74.71.01.1001' => '74.71', // Kendari
            '72.71.01.1001' => '72.71', // Palu
            '76.01.01.1001' => '76.01', // Mamuju
            '81.71.01.1001' => '81.71', // Ambon
            '92.71.01.1001' => '92.71', // Sorong
            '91.01.01.1001' => '91.01', // Merauke
            '91.04.01.1001' => '91.04', // Nabire
            '91.02.01.1001' => '91.02', // Wamena
        ];

        // 1. Cek apakah masuk daftar override, coba tembak pakai kode alternatifnya
        if (isset($overrideMap[$areaCode])) {
            $altCode = $overrideMap[$areaCode];
            $data = $this->queryBmkgApi($baseUrl, $altCode);
            if (!empty($data)) {
                DB::table('bmkg_regions')->where('area_code', $areaCode)->update(['area_code' => $altCode]);
                return $data;
            }
        }

        try {
            // 2. Primary Attempt (Full ADM4 Code)
            $data = $this->queryBmkgApi($baseUrl, $areaCode);
            if (!empty($data)) return $data;

            // 3. Automatic Fallback (Shortened to ADM2 format)
            if (strlen($areaCode) > 5) {
                $fallbackCode = substr($areaCode, 0, 5);
                $data = $this->queryBmkgApi($baseUrl, $fallbackCode);
                
                if (!empty($data)) {
                    // Self-Healing: Update database so subsequent runs use the working code
                    DB::table('bmkg_regions')->where('area_code', $areaCode)->update(['area_code' => $fallbackCode]);
                    return $data;
                }
            }
        } catch (\Throwable $e) {
            Log::error("BMKG: Fetch cycle failed for $areaCode: " . $e->getMessage());
        }

        // 4. OpenWeatherMap Fallback (Coordinate-based)
        if ($lat !== null && $lon !== null) {
            $data = $this->queryOpenWeatherApi($lat, $lon);
            if (!empty($data)) {
                return $data;
            }
        }

        $this->failed("All fallbacks (BMKG ADM4/ADM2 + OWM) failed for $areaCode.");
        return [];
    }

    /**
     * Query OpenWeatherMap API as final fallback using coordinates.
     */
    private function queryOpenWeatherApi(float $lat, float $lon): array
    {
        $apiKey = config('services.openweather.api_key');
        
        if (!$apiKey) {
            Log::warning('OpenWeatherMap API key not configured, skipping fallback');
            return [];
        }

        try {
            $response = Http::timeout(10)
                ->retry(2, 200)
                ->get('https://api.openweathermap.org/data/2.5/weather', [
                    'lat' => $lat,
                    'lon' => $lon,
                    'appid' => $apiKey,
                    'units' => 'metric',
                ]);

            if ($response->successful()) {
                $raw = $response->json();
                
                return [
                    'temperature' => $raw['main']['temp'] ?? null,
                    'humidity' => $raw['main']['humidity'] ?? null,
                    'wind_speed' => $raw['wind']['speed'] ?? null,
                    'wind_direction' => $raw['wind']['deg'] ?? null,
                    'recorded_at' => now()->toDateTimeString(),
                ];
            }

            Log::warning("OpenWeatherMap: No data for lat=$lat, lon=$lon (Status: {$response->status()})");
        } catch (\Throwable $e) {
            Log::error("OpenWeatherMap: Exception for lat=$lat, lon=$lon: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Helper to call BMKG API endpoint strictly using the 'adm4' parameter key.
     */
    private function queryBmkgApi(string $baseUrl, string $code): array
    {
        try {
            $response = Http::timeout(10)
                ->retry(2, 200)
                ->get($baseUrl . '/publik/prakiraan-cuaca', [
                    'adm4' => $code
                ]);

            if ($response->successful()) {
                $raw = $response->json();
                
                if (!empty($raw) && isset($raw['data'][0]['cuaca'][0])) {
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
                }
            }
            
            Log::warning("BMKG: No data returned for code $code (Status: {$response->status()})");
        } catch (\Throwable $e) {
            Log::error("BMKG: Exception for code $code: " . $e->getMessage());
        }

        return [];
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