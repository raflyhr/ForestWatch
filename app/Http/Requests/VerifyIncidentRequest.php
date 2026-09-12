<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'result' => ['required', 'in:fire_confirmed,smoke_only,false_alarm,unable_to_verify'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
