<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WeatherSnapshot;

class WeatherController extends Controller
{
    public function index()
    {
        return WeatherSnapshot::latest()->paginate(25);
    }
}
