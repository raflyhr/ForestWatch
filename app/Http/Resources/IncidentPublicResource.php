<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentPublicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'warning_level' => $this->warning_level,
            'confidence' => $this->confidence,
            'reports_count' => $this->whenCounted('reports'),
            'hotspots_count' => $this->whenCounted('hotspots'),
            'created_at' => $this->created_at,
        ];
    }
}
