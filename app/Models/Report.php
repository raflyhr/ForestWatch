<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'report_type', 'latitude', 'longitude', 'photo_url',
        'description', 'phone_number', 'status', 'incident_id',
    ];

    protected $hidden = ['phone_number'];

    public function incident()   { return $this->belongsTo(Incident::class); }
    public function aiAssessment() { return $this->hasOne(AiAssessment::class); }
}