<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\Warning;

class WarningEngine
{
    public function recalculate(Incident $incident): void
    {
        $score = 0;
        $reasons = [];

        if ($incident->hotspots()->exists()) {
            $score += config('forestwatch.weights.nasa_hotspot', 40);
            $reasons[] = 'NASA hotspot terdeteksi';
        }

        $reportCount = $incident->reports()->where('status', '!=', 'invalid')->count();
        if ($reportCount > 0) {
            $score += min($reportCount * config('forestwatch.weights.per_report', 20), 40);
            $reasons[] = "{$reportCount} laporan masyarakat";
        }

        $aiPositive = $incident->reports()
            ->whereHas('aiAssessment', fn ($q) => $q->where('fire_score', '>', 0.5))
            ->exists();
        if ($aiPositive) {
            $score += config('forestwatch.weights.ai_evidence', 15);
            $reasons[] = 'AI mendeteksi indikasi api/asap pada foto';
        }

        [$level, $confidence] = $this->scoreToLevel($score, $incident);

        $incident->update(['warning_level' => $level, 'confidence' => $confidence]);

        Warning::create([
            'incident_id' => $incident->id,
            'level' => $level,
            'score' => $score,
            'reason' => implode('; ', $reasons),
        ]);
    }

    private function scoreToLevel(int $score, Incident $incident): array
    {
        $level = match (true) {
            $score >= 80 => 'critical',
            $score >= 55 => 'high',
            $score >= 30 => 'medium',
            default      => 'low',
        };

        $evidenceCount = $incident->hotspots()->count()
            + $incident->reports()->count()
            + ($incident->weatherSnapshots()->exists() ? 1 : 0);

        $confidence = $evidenceCount >= 3 ? 'high' : ($evidenceCount == 2 ? 'medium' : 'low');

        return [$level, $confidence];
    }
}