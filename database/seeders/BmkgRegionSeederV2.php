<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BmkgRegionSeederV2 extends Seeder
{
    public function run(): void
    {
        $regions = [
            // JAVA
            ['area_code' => '31.71.01.1001', 'name' => 'Gambir, Jakarta Pusat', 'latitude' => -6.1764, 'longitude' => 106.8267, 'province' => 'DKI Jakarta', 'island_group' => 'Java'],
            ['area_code' => '32.73.01.1001', 'name' => 'Bandung', 'latitude' => -6.9147, 'longitude' => 107.6098, 'province' => 'Jawa Barat', 'island_group' => 'Java'],
            ['area_code' => '33.74.01.1001', 'name' => 'Semarang', 'latitude' => -7.0051, 'longitude' => 110.4381, 'province' => 'Jawa Tengah', 'island_group' => 'Java'],
            ['area_code' => '35.78.01.1001', 'name' => 'Surabaya', 'latitude' => -7.2575, 'longitude' => 112.7521, 'province' => 'Jawa Timur', 'island_group' => 'Java'],
            ['area_code' => '34.71.01.1001', 'name' => 'Yogyakarta', 'latitude' => -7.7956, 'longitude' => 110.3695, 'province' => 'DI Yogyakarta', 'island_group' => 'Java'],
            ['area_code' => '32.75.01.1001', 'name' => 'Bekasi', 'latitude' => -6.2383, 'longitude' => 106.9756, 'province' => 'Jawa Barat', 'island_group' => 'Java'],

            // SUMATRA
            ['area_code' => '11.71.01.1001', 'name' => 'Banda Aceh', 'latitude' => 5.5483, 'longitude' => 95.3238, 'province' => 'Aceh', 'island_group' => 'Sumatra'],
            ['area_code' => '12.71.01.1001', 'name' => 'Medan', 'latitude' => 3.5952, 'longitude' => 98.6722, 'province' => 'Sumatera Utara', 'island_group' => 'Sumatra'],
            ['area_code' => '14.71.01.1001', 'name' => 'Pekanbaru', 'latitude' => 0.5071, 'longitude' => 101.4478, 'province' => 'Riau', 'island_group' => 'Sumatra'],
            ['area_code' => '13.71.01.1001', 'name' => 'Padang', 'latitude' => -0.9471, 'longitude' => 100.4172, 'province' => 'Sumatera Barat', 'island_group' => 'Sumatra'],
            ['area_code' => '16.71.01.1001', 'name' => 'Palembang', 'latitude' => -2.9909, 'longitude' => 104.7565, 'province' => 'Sumatera Selatan', 'island_group' => 'Sumatra'],
            ['area_code' => '18.71.01.1001', 'name' => 'Bandar Lampung', 'latitude' => -5.4254, 'longitude' => 105.2580, 'province' => 'Lampung', 'island_group' => 'Sumatra'],
            ['area_code' => '15.71.01.1001', 'name' => 'Jambi', 'latitude' => -1.6101, 'longitude' => 103.6131, 'province' => 'Jambi', 'island_group' => 'Sumatra'],
            ['area_code' => '17.71.01.1001', 'name' => 'Bengkulu', 'latitude' => -3.7928, 'longitude' => 102.2608, 'province' => 'Bengkulu', 'island_group' => 'Sumatra'],

            // KALIMANTAN
            ['area_code' => '61.71.01.1001', 'name' => 'Pontianak', 'latitude' => -0.0263, 'longitude' => 109.3425, 'province' => 'Kalimantan Barat', 'island_group' => 'Kalimantan'],
            ['area_code' => '62.71.01.1001', 'name' => 'Palangkaraya', 'latitude' => -2.2084, 'longitude' => 113.9167, 'province' => 'Kalimantan Tengah', 'island_group' => 'Kalimantan'],
            ['area_code' => '63.71.01.1001', 'name' => 'Banjarmasin', 'latitude' => -3.3186, 'longitude' => 114.5907, 'province' => 'Kalimantan Selatan', 'island_group' => 'Kalimantan'],
            ['area_code' => '64.71.01.1001', 'name' => 'Balikpapan', 'latitude' => -1.2615, 'longitude' => 116.8282, 'province' => 'Kalimantan Timur', 'island_group' => 'Kalimantan'],
            ['area_code' => '64.72.01.1001', 'name' => 'Samarinda', 'latitude' => -0.4948, 'longitude' => 117.1436, 'province' => 'Kalimantan Timur', 'island_group' => 'Kalimantan'],
            ['area_code' => '65.71.01.1001', 'name' => 'Tarakan', 'latitude' => 3.3274, 'longitude' => 117.5855, 'province' => 'Kalimantan Utara', 'island_group' => 'Kalimantan'],

            // SULAWESI
            ['area_code' => '73.71.01.1001', 'name' => 'Makassar', 'latitude' => -5.1477, 'longitude' => 119.4327, 'province' => 'Sulawesi Selatan', 'island_group' => 'Sulawesi'],
            ['area_code' => '71.71.01.1001', 'name' => 'Manado', 'latitude' => 1.4748, 'longitude' => 124.8421, 'province' => 'Sulawesi Utara', 'island_group' => 'Sulawesi'],
            ['area_code' => '72.71.01.1001', 'name' => 'Palu', 'latitude' => -0.8917, 'longitude' => 119.8707, 'province' => 'Sulawesi Tengah', 'island_group' => 'Sulawesi'],
            ['area_code' => '74.71.01.1001', 'name' => 'Kendari', 'latitude' => -3.9722, 'longitude' => 122.5149, 'province' => 'Sulawesi Tenggara', 'island_group' => 'Sulawesi'],
            ['area_code' => '75.71.01.1001', 'name' => 'Gorontalo', 'latitude' => 0.5435, 'longitude' => 123.0568, 'province' => 'Gorontalo', 'island_group' => 'Sulawesi'],
            ['area_code' => '76.01.01.1001', 'name' => 'Mamuju', 'latitude' => -2.6778, 'longitude' => 118.8875, 'province' => 'Sulawesi Barat', 'island_group' => 'Sulawesi'],

            // BALI & NUSA TENGGARA
            ['area_code' => '51.71.01.1001', 'name' => 'Denpasar', 'latitude' => -8.6705, 'longitude' => 115.2126, 'province' => 'Bali', 'island_group' => 'Bali & NT'],
            ['area_code' => '52.71.01.1001', 'name' => 'Mataram', 'latitude' => -8.5796, 'longitude' => 116.0951, 'province' => 'Nusa Tenggara Barat', 'island_group' => 'Bali & NT'],
            ['area_code' => '53.71.01.1001', 'name' => 'Kupang', 'latitude' => -10.1772, 'longitude' => 123.6070, 'province' => 'Nusa Tenggara Timur', 'island_group' => 'Bali & NT'],

            // MALUKU & PAPUA
            ['area_code' => '81.71.01.1001', 'name' => 'Ambon', 'latitude' => -3.6547, 'longitude' => 128.1906, 'province' => 'Maluku', 'island_group' => 'Maluku & Papua'],
            ['area_code' => '82.71.01.1001', 'name' => 'Ternate', 'latitude' => 0.7906, 'longitude' => 127.3842, 'province' => 'Maluku Utara', 'island_group' => 'Maluku & Papua'],
            ['area_code' => '91.71.01.1001', 'name' => 'Jayapura', 'latitude' => -2.5916, 'longitude' => 140.6690, 'province' => 'Papua', 'island_group' => 'Maluku & Papua'],
            ['area_code' => '92.71.01.1001', 'name' => 'Sorong', 'latitude' => -0.8615, 'longitude' => 131.2520, 'province' => 'Papua Barat', 'island_group' => 'Maluku & Papua'],
            ['area_code' => '93.71.01.1001', 'name' => 'Merauke', 'latitude' => -8.4991, 'longitude' => 140.3924, 'province' => 'Papua Selatan', 'island_group' => 'Maluku & Papua'],
            ['area_code' => '94.71.01.1001', 'name' => 'Nabire', 'latitude' => -3.3661, 'longitude' => 135.4831, 'province' => 'Papua Tengah', 'island_group' => 'Maluku & Papua'],
            ['area_code' => '95.71.01.1001', 'name' => 'Wamena', 'latitude' => -4.0950, 'longitude' => 138.9439, 'province' => 'Papua Pegunungan', 'island_group' => 'Maluku & Papua'],
        ];

        try {
            foreach ($regions as $region) {
                DB::table('bmkg_regions')->updateOrInsert(
                    ['area_code' => $region['area_code']],
                    array_merge($region, ['updated_at' => now(), 'created_at' => now()])
                );
            }
            Log::info('BmkgRegionSeederV2 executed with ' . count($regions) . ' nationwide coverage points.');
        } catch (\Throwable $e) {
            Log::error('BmkgRegionSeederV2 failed: ' . $e->getMessage());
            throw $e;
        }
    }
}