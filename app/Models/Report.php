<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'report_type', 'latitude', 'longitude', 'photo_url',
        'description', 'phone_number', 'status', 'incident_id', 'photo_hash',
        'is_duplicate', 'duplicate_reason',
    ];

    protected $hidden = ['phone_number'];

    protected function casts(): array
    {
        return ['latitude' => 'float', 'longitude' => 'float', 'is_duplicate' => 'boolean'];
    }

    public function incident()   { return $this->belongsTo(Incident::class); }
    public function aiAssessment() { return $this->hasOne(AiAssessment::class); }
}
