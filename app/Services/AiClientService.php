<?php

namespace App\Services;

use App\Models\Report;
use Illuminate\Support\Facades\Http;
use App\Models\IntegrationStatus;
use App\Models\ActivityLog;

class AiClientService
{
    public function analyzePhoto(Report $report): ?array
    {
        try {
            $response = Http::timeout(10)
                ->post(config('services.ai.url') . '/analyze', [
                    'report_id' => $report->id,
                    'photo_url' => $report->photo_url,
                ]);

            if ($response->failed()) {
                $status = IntegrationStatus::updateOrCreate(['source' => 'ai'], [
                    'status' => 'failed', 'last_failure_at' => now(),
                    'last_error' => 'AI service returned HTTP '.$response->status(),
                ]);
                ActivityLog::create([
                    'action' => 'integration.failed', 'target_type' => IntegrationStatus::class,
                    'target_id' => $status->id, 'new_values' => ['source' => 'ai', 'error' => $status->last_error],
                ]);
                report(new \Exception('AI service error: ' . $response->status()));
                return null;
            }

            $result = $response->json();
            IntegrationStatus::updateOrCreate(['source' => 'ai'], [
                'status' => 'healthy', 'last_success_at' => now(),
                'last_error' => null, 'last_record_count' => 1,
            ]);
            return $result;
        } catch (\Throwable $e) {
            $status = IntegrationStatus::updateOrCreate(['source' => 'ai'], [
                'status' => 'failed', 'last_failure_at' => now(), 'last_error' => $e->getMessage(),
            ]);
            ActivityLog::create([
                'action' => 'integration.failed', 'target_type' => IntegrationStatus::class,
                'target_id' => $status->id, 'new_values' => ['source' => 'ai', 'error' => $e->getMessage()],
            ]);
            report($e);
            return null;
        }
    }
}
