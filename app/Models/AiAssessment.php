<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiAssessment extends Model
{
    protected $fillable = ['report_id', 'fire_score', 'smoke_score', 'analysis_data'];
    public function report() { return $this->belongsTo(Report::class); }
}