<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Response extends Model
{
    protected $fillable = ['incident_id', 'status', 'notes'];
    public function incident() { return $this->belongsTo(Incident::class); }
}