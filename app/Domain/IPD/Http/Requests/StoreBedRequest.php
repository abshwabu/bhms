<?php

namespace App\Domain\IPD\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ward_id' => ['required', 'uuid', 'exists:wards,id'],
            'bed_number' => ['required', 'string', 'max:30'],
            'bed_type' => ['nullable', Rule::in(['standard', 'electric', 'icu_ventilator', 'crib', 'bariatric'])],
            'features' => ['nullable', 'array'],
            'daily_rate_override' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
