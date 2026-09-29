<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiAssessment extends Model
{
    protected function casts(): array
    {
        return ['fire_score' => 'float', 'smoke_score' => 'float', 'analysis_data' => 'array'];
    }

    protected $fillable = ['report_id', 'fire_score', 'smoke_score', 'analysis_data'];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }
}
