<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaterSource extends Model
{
    protected $fillable = ['name', 'type', 'latitude', 'longitude', 'source', 'is_verified'];

    protected function casts(): array
    {
        return ['latitude' => 'float', 'longitude' => 'float', 'is_verified' => 'boolean'];
    }
}
