<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class NasaFirmsService
{
    public function fetchActiveFires(string $areaBoundingBox): array
    {
        $response = Http::get('https://firms.modaps.eosdis.nasa.gov/api/area/csv/' .
            config('services.nasa.api_key') . "/VIIRS_SNPP_NRT/{$areaBoundingBox}/1");

        if ($response->failed()) {
            report(new \Exception('NASA FIRMS fetch failed: ' . $response->status()));
            return [];
        }

        return $this->parseCsv($response->body());
    }

    private function parseCsv(string $csv): array
    {
        $lines = array_map('str_getcsv', explode("\n", trim($csv)));
        $header = array_shift($lines);
        if (!$header) return [];
        return array_map(fn ($row) => array_combine($header, $row), $lines);
    }
}