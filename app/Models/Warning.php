<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warning extends Model
{
    protected $fillable = ['incident_id', 'level', 'score', 'reason'];

    public function incident()
    {
        return $this->belongsTo(Incident::class);
    }
}
