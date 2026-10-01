<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WaterSource;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate([
            'email' => 'admin123@gmail.com',
        ], [
            'name' => 'Admin ForestWatch',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        foreach ([
            ['name' => 'Embung Bukit Raya', 'type' => 'pond', 'latitude' => -2.05, 'longitude' => 113.95],
            ['name' => 'Sungai Kahayan Pos 1', 'type' => 'river', 'latitude' => -2.22, 'longitude' => 113.82],
            ['name' => 'Danau Tahai', 'type' => 'lake', 'latitude' => -2.18, 'longitude' => 114.02],
            ['name' => 'Hydrant Posko Kecamatan', 'type' => 'hydrant', 'latitude' => -2.31, 'longitude' => 113.88],
        ] as $source) {
            WaterSource::updateOrCreate(['name' => $source['name']], [...$source, 'source' => 'district_team', 'is_verified' => true]);
        }
    }
}
