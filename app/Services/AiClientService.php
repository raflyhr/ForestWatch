<?php

namespace App\Services;

use App\Models\Report;
use Illuminate\Support\Facades\Http;

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
                report(new \Exception('AI service error: ' . $response->status()));
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }
}