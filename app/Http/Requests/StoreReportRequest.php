<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_type' => ['required', 'in:smoke,fire,smoke_fire'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'phone_number' => ['required', 'regex:/^[0-9+\-\s]{8,15}$/'],
        ];
    }
}
