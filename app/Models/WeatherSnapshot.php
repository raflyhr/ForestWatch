<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeatherSnapshot extends Model
{
    protected $fillable = ['incident_id', 'temperature', 'humidity', 'wind_speed', 'wind_direction'];
    public function incident() { return $this->belongsTo(Incident::class); }
}