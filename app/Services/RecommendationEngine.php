<?php

namespace App\Services;

use App\Models\Incident;

class RecommendationEngine
{
    public function recommend(Incident $incident): string
    {
        return match ($incident->warning_level) {
            'low' => 'Monitor kondisi dan tunggu evidence tambahan.',
            'medium' => 'Perhatikan perkembangan dan lakukan verifikasi apabila diperlukan.',
            'high' => 'Prioritaskan verifikasi lokasi.',
            'critical' => 'Prioritaskan verifikasi dan respons sesuai prosedur.',
            default => 'Belum ada rekomendasi — evidence belum cukup.',
        };
    }
}
