<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hotspot;

class HotspotController extends Controller
{
    public function index()
    {
        return Hotspot::latest('detected_at')->paginate(25);
    }
}
