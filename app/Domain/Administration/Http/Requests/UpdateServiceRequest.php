<?php

namespace App\Domain\Administration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'name' => ['sometimes', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'in:clinical,surgical,diagnostic,nursing,emergency,administrative'],
            'description' => ['nullable', 'string'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:480'],
            'base_price_cents' => ['nullable', 'integer', 'min:0'],
            'requires_doctor' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'preparation_instructions' => ['nullable', 'array'],
        ];
    }
}
