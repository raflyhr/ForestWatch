<?php

use App\Models\IntegrationStatus;
use App\Services\NasaFirmsService;
use Illuminate\Support\Facades\Http;

it('parses valid NASA CSV rows and records healthy integration status', function () {
    Http::fake([
        'firms.modaps.eosdis.nasa.gov/*' => Http::response("latitude,longitude,acq_date,acq_time\n-6.2,106.8,2026-09-12,1234\n", 200),
    ]);

    $rows = app(NasaFirmsService::class)->fetchActiveFires('95,-11,141,6');

    expect($rows)->toHaveCount(1)
        ->and(IntegrationStatus::where('source', 'nasa')->value('status'))->toBe('healthy');
});

it('records failed NASA integration without throwing', function () {
    Http::fake(['firms.modaps.eosdis.nasa.gov/*' => Http::response([], 503)]);

    expect(app(NasaFirmsService::class)->fetchActiveFires('95,-11,141,6'))->toBe([])
        ->and(IntegrationStatus::where('source', 'nasa')->value('status'))->toBe('failed');
});
