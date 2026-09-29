<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hotspot extends Model
{
    protected $fillable = ['latitude', 'longitude', 'detected_at', 'satellite', 'confidence', 'frp', 'source', 'incident_id'];

    public function incident()
    {
        return $this->belongsTo(Incident::class);
    }
}
