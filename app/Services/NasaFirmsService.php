<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\IntegrationStatus;
use App\Models\ActivityLog;

class NasaFirmsService
{
    public function fetchActiveFires(string $areaBoundingBox): array
    {
        $allRows = [];
        $apiKey = config('services.nasa.api_key');
        $satellites = [
            'VIIRS_SNPP_NRT' => 2,
            'MODIS_NRT' => 1,
        ];

        foreach ($satellites as $satellite => $days) {
            try {
                $response = Http::timeout(10)->retry(2, 200)->get(
                    "https://firms.modaps.eosdis.nasa.gov/api/area/csv/{$apiKey}/{$satellite}/{$areaBoundingBox}/{$days}"
                );

                if ($response->successful()) {
                    $rows = $this->parseCsv($response->body());
                    $allRows = array_merge($allRows, $rows);
                } else {
                    $this->failed("NASA FIRMS {$satellite} fetch failed: HTTP " . $response->status());
                }
            } catch (\Throwable $e) {
                $this->failed("NASA FIRMS {$satellite} error: " . $e->getMessage());
            }
        }

        if (!empty($allRows)) {
            IntegrationStatus::updateOrCreate(['source' => 'nasa'], [
                'status' => 'healthy',
                'last_success_at' => now(),
                'last_error' => null,
                'last_record_count' => count($allRows),
            ]);
        }

        return $allRows;
    }

    private function parseCsv(string $csv): array
    {
        $lines = array_values(array_filter(array_map('str_getcsv', explode("\n", trim($csv))), fn ($row) => $row !== ['']));
        $header = array_shift($lines);
        if (!$header) return [];
        return array_values(array_filter(array_map(function ($row) use ($header) {
            if (count($row) !== count($header)) {
                return null;
            }

            $item = array_combine($header, $row);
            return is_numeric($item['latitude'] ?? null) && is_numeric($item['longitude'] ?? null)
                && (float) $item['latitude'] >= -90 && (float) $item['latitude'] <= 90
                && (float) $item['longitude'] >= -180 && (float) $item['longitude'] <= 180
                ? $item : null;
        }, $lines)));
    }

    private function failed(string $message): void
    {
        report(new \Exception($message));
        $status = IntegrationStatus::updateOrCreate(['source' => 'nasa'], [
            'status' => 'failed', 'last_failure_at' => now(), 'last_error' => $message,
        ]);
        ActivityLog::create([
            'action' => 'integration.failed', 'target_type' => IntegrationStatus::class,
            'target_id' => $status->id, 'new_values' => ['source' => 'nasa', 'error' => $message],
        ]);
    }
}
