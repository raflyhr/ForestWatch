<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProcessNasaHotspotsRegion;

class FetchNasaHotspots extends Command
{
    protected $signature = 'forestwatch:fetch-nasa';
    protected $description = 'Fetch active fire hotspots from NASA FIRMS via regional jobs';

    public function handle()
    {
        if (! config('services.nasa.api_key')) {
            $this->error('NASA_FIRMS_API_KEY is not configured.');
            return self::FAILURE;
        }

        foreach (config('forestwatch.regions') as $regionName => $boundingBox) {
            ProcessNasaHotspotsRegion::dispatch($regionName, $boundingBox);
        }

        $this->info('Processing started for all regions. Check queue worker for progress.');
    }
}
