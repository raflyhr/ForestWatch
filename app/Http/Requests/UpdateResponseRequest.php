<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:assigned,on_the_way,on_site,completed'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
