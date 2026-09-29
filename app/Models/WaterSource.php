<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaterSource extends Model
{
    protected $fillable = ['name', 'type', 'latitude', 'longitude'];
}
