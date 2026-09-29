<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Verification extends Model
{
    protected $fillable = ['incident_id', 'officer_id', 'result', 'notes', 'verified_at'];

    public function incident()
    {
        return $this->belongsTo(Incident::class);
    }

    public function officer()
    {
        return $this->belongsTo(User::class, 'officer_id');
    }
}
