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

        // 4. OpenWeatherMap Fallback (TEMPORARILY DISABLED FOR BMKG ANALYSIS)
        /*
        if ($lat !== null && $lon !== null) {
            $data = $this->queryOpenWeatherApi($lat, $lon);
            if (!empty($data)) {
                return $data;
            }
        }
        */

        $this->failed("BMKG primary and fallback attempts completed (OWM fallback skipped for analysis) for $areaCode.");
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
        // Validasi: Hanya kirim kode ADM4 (4 level: provinsi.kabupaten.kecamatan.desa)
        // Format: XX.XX.XX.XXXX (13 karakter dengan titik)
        if (!preg_match('/^\d{2}\.\d{2}\.\d{2}\.\d{4}$/', $code)) {
            Log::warning("BMKG: Skipping non-ADM4 code: $code (expected format: XX.XX.XX.XXXX)");
            return [];
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36'
            ])
            ->timeout(15)
            ->retry(2, 500)
            ->get($baseUrl . '/publik/prakiraan-cuaca', [
                'adm4' => $code
            ]);

            Log::info("BMKG Raw Response for $code: " . $response->body());

            if ($response->successful()) {
                $raw = $response->json();
                
                $dataList = $raw['data'] ?? [];
                if (empty($dataList)) return [];

                $regionData = is_array($dataList) && isset($dataList[0]) ? $dataList[0] : $dataList;
                $cuacaGroups = $regionData['cuaca'] ?? [];

                if (empty($cuacaGroups)) return [];

                // Flatten multidimensional cuaca array (array of arrays of forecast objects)
                $allForecasts = [];
                foreach ($cuacaGroups as $group) {
                    if (is_array($group)) {
                        foreach ($group as $forecast) {
                            if (is_array($forecast) && isset($forecast['local_datetime'])) {
                                $allForecasts[] = $forecast;
                            }
                        }
                    }
                }

                if (empty($allForecasts)) return [];

                // Find forecast closest to now
                $now = now();
                $closestForecast = null;
                $minDiff = PHP_INT_MAX;

                foreach ($allForecasts as $forecast) {
                    $dt = \Carbon\Carbon::parse($forecast['local_datetime'] ?? '');
                    if (!$dt->isValid()) continue;

                    $diff = abs($now->getTimestamp() - $dt->getTimestamp());
                    if ($diff < $minDiff) {
                        $minDiff = $diff;
                        $closestForecast = $forecast;
                    }
                }

                if ($closestForecast) {
                    IntegrationStatus::updateOrCreate(['source' => 'bmkg'], [
                        'status' => 'healthy', 
                        'last_success_at' => now(),
                        'last_error' => null, 
                        'last_record_count' => 1,
                    ]);

                    return [
                        'temperature' => $closestForecast['t'] ?? null,
                        'humidity' => $closestForecast['hu'] ?? null,
                        'wind_speed' => $closestForecast['ws'] ?? null,
                        'wind_direction' => $closestForecast['wd'] ?? null,
                        'weather_desc' => $closestForecast['weather_desc'] ?? $closestForecast['weather'] ?? null,
                        'recorded_at' => $closestForecast['local_datetime'] ?? now()->toDateTimeString(),
                    ];
                }
            }
            
            Log::warning("BMKG: No valid forecast in response for code $code (Status: {$response->status()})");
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