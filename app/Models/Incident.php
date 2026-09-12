<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    protected $fillable = ['latitude', 'longitude', 'status', 'warning_level', 'confidence'];

    protected function casts(): array
    {
        return ['latitude' => 'float', 'longitude' => 'float'];
    }

    public function hotspots()      { return $this->hasMany(Hotspot::class); }
    public function reports()       { return $this->hasMany(Report::class); }
    public function weatherSnapshots() { return $this->hasMany(WeatherSnapshot::class); }
    public function warnings()      { return $this->hasMany(Warning::class); }
    public function verifications() { return $this->hasMany(Verification::class); }
    public function responses()     { return $this->hasMany(Response::class); }
}
