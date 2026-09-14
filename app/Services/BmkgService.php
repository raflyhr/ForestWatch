<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\IntegrationStatus;
use App\Models\ActivityLog;

class BmkgService
{
    public function fetchWeatherData(string $areaCode): array
    {
        try {
            $response = Http::timeout(10)->retry(2, 200)->get(config('services.bmkg.base_url') . '/weather/' . $areaCode);

            if ($response->failed()) {
                $this->failed('BMKG fetch failed: HTTP ' . $response->status());
                return [];
            }

            $data = $response->json();
            IntegrationStatus::updateOrCreate(['source' => 'bmkg'], [
                'status' => 'healthy', 'last_success_at' => now(),
                'last_error' => null, 'last_record_count' => count($data),
            ]);
            return $data;
        } catch (\Throwable $e) {
            $this->failed($e->getMessage());
            return [];
        }
    }

    private function failed(string $message): void
    {
        report(new \Exception($message));
        $status = IntegrationStatus::updateOrCreate(['source' => 'bmkg'], [
            'status' => 'failed', 'last_failure_at' => now(), 'last_error' => $message,
        ]);
        ActivityLog::create([
            'action' => 'integration.failed', 'target_type' => IntegrationStatus::class,
            'target_id' => $status->id, 'new_values' => ['source' => 'bmkg', 'error' => $message],
        ]);
    }
}
