<?php

namespace App\Jobs;

use App\Models\Report;
use App\Services\AiClientService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeReportWithAi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(public Report $report) {}

    public function handle(AiClientService $ai): void
    {
        $result = $ai->analyzePhoto($this->report);
        if (! $result || ! isset($result['fire_score'], $result['smoke_score'])) {
            return;
        }

        $this->report->aiAssessment()->updateOrCreate([], [
            'fire_score' => (float) $result['fire_score'],
            'smoke_score' => (float) $result['smoke_score'],
            'analysis_data' => $result,
        ]);
    }
}
