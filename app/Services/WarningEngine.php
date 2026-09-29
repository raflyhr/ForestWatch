<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\SystemSetting;
use App\Models\Warning;

class WarningEngine
{
    public function recalculate(Incident $incident): void
    {
        $score = 0;
        $reasons = [];

        if ($incident->hotspots()->exists()) {
            $score += $this->setting('warning.weights.nasa_hotspot', config('forestwatch.weights.nasa_hotspot', 40));
            $reasons[] = 'NASA hotspot terdeteksi';
        }

        $reportCount = $incident->reports()->where('status', '!=', 'invalid')->count();
        if ($reportCount > 0) {
            $score += min($reportCount * $this->setting('warning.weights.per_report', config('forestwatch.weights.per_report', 20)), 40);
            $reasons[] = "{$reportCount} laporan masyarakat";
        }

        $aiPositive = $incident->reports()
            ->whereHas('aiAssessment', fn ($q) => $q->where('fire_score', '>', 0.5))
            ->exists();
        if ($aiPositive) {
            $score += $this->setting('warning.weights.ai_evidence', config('forestwatch.weights.ai_evidence', 15));
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
        $thresholds = [
            'medium' => $this->setting('warning.thresholds.medium', config('forestwatch.thresholds.medium', 30)),
            'high' => $this->setting('warning.thresholds.high', config('forestwatch.thresholds.high', 55)),
            'critical' => $this->setting('warning.thresholds.critical', config('forestwatch.thresholds.critical', 80)),
        ];
        $level = match (true) {
            $score >= $thresholds['critical'] => 'critical',
            $score >= $thresholds['high'] => 'high',
            $score >= $thresholds['medium'] => 'medium',
            default => 'low',
        };

        $evidenceCount = $incident->hotspots()->count()
            + $incident->reports()->where('status', '!=', 'invalid')->count()
            + ($incident->weatherSnapshots()->exists() ? 1 : 0);

        $confidence = $evidenceCount >= 3 ? 'high' : ($evidenceCount == 2 ? 'medium' : 'low');

        return [$level, $confidence];
    }

    private function setting(string $key, int $fallback): int
    {
        return (int) (SystemSetting::where('key', $key)->value('value') ?? $fallback);
    }
}
