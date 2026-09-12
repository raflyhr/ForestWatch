<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WaterSource;

class WaterSourceController extends Controller
{
    public function index()
    {
        return WaterSource::query()->select(['id', 'name', 'type', 'latitude', 'longitude'])->paginate(25);
    }
}
