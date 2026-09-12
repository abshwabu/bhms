<?php

namespace App\Domain\Administration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:150'],
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
