<?php

namespace App\Domain\Clinical\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AmendEhrRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amendment_reason' => ['required', 'string', 'min:5', 'max:1000'],
            'title' => ['nullable', 'string', 'max:255'],
            'record_type' => ['nullable', 'string'],
            'category' => ['nullable', 'string'],
            'clinical_notes' => ['required', 'array'],
            'vitals' => ['nullable', 'array'],
        ];
    }
}
