<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BmkgService;
use App\Models\WeatherSnapshot;
use Illuminate\Support\Carbon;

class FetchBmkgWeather extends Command
{
    protected $signature = 'forestwatch:fetch-bmkg';
    protected $description = 'Fetch weather data from BMKG';

    public function handle(BmkgService $bmkg)
    {
        if (! config('services.bmkg.base_url')) {
            $this->error('BMKG_API_BASE is not configured.');
            return self::FAILURE;
        }

        $data = $bmkg->fetchWeatherData(config('forestwatch.area_code', '501210'));

        foreach ($data as $item) {
            WeatherSnapshot::create([
                'area_code' => $item['area_code'],
                'temperature' => $item['temperature'],
                'humidity' => $item['humidity'],
                'wind_speed' => $item['wind_speed'],
                'wind_direction' => $item['wind_direction'],
                'recorded_at' => Carbon::parse($item['recorded_at']),
            ]);
        }

        $this->info(count($data) . ' weather snapshots processed.');
    }
}
