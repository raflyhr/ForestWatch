<?php

namespace App\Services;

use App\Models\Report;

class DuplicateReportDetector
{
    public function isDuplicate(array $data): bool
    {
        $lat = (float) $data['latitude'];
        $lng = (float) $data['longitude'];
        $latDelta = 500 / 111045;
        $lngDelta = 500 / (111045 * max(cos(deg2rad($lat)), 0.01));

        return Report::query()
            ->where('created_at', '>=', now()->subHours(24))
            ->where(function ($query) use ($data, $lat, $lng, $latDelta, $lngDelta) {
                $query->when($data['photo_hash'] ?? null, fn ($query, $hash) => $query->where('photo_hash', $hash))
                    ->orWhere(function ($query) use ($data, $lat, $lng, $latDelta, $lngDelta) {
                        $query->where('report_type', $data['report_type'])
                            ->where('phone_number', $data['phone_number'])
                            ->whereBetween('latitude', [$lat - $latDelta, $lat + $latDelta])
                            ->whereBetween('longitude', [$lng - $lngDelta, $lng + $lngDelta]);
                    });
            })
            ->exists();
    }
}
