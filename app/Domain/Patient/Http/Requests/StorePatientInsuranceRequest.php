<?php

namespace App\Domain\Patient\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientInsuranceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider_name' => ['required', 'string', 'max:255'],
            'policy_number' => ['required', 'string', 'max:100'],
            'group_number' => ['nullable', 'string', 'max:100'],
            'coverage_type' => ['nullable', Rule::in(['primary', 'secondary', 'tertiary'])],
            'coverage_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'copay_amount_cents' => ['nullable', 'integer', 'min:0'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'pre_auth_required' => ['boolean'],
            'status' => ['nullable', Rule::in(['active', 'expired', 'cancelled', 'pending_verification'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
