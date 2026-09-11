<?php

namespace App\Domain\Patient\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $sanitized = [];

        if ($this->has('first_name')) {
            $sanitized['first_name'] = strip_tags(trim($this->input('first_name')));
        }
        if ($this->has('last_name')) {
            $sanitized['last_name'] = strip_tags(trim($this->input('last_name')));
        }
        if ($this->has('middle_name')) {
            $sanitized['middle_name'] = strip_tags(trim($this->input('middle_name')));
        }
        if ($this->has('email')) {
            $sanitized['email'] = strtolower(trim($this->input('email')));
        }
        if ($this->has('phone')) {
            $phone = preg_replace('/[^\d+]/', '', trim($this->input('phone')));
            $sanitized['phone'] = $phone ?: null;
        }
        if ($this->has('national_id')) {
            $sanitized['national_id'] = strtoupper(trim(strip_tags($this->input('national_id'))));
        }

        $this->merge($sanitized);
    }

    public function rules(): array
    {
        $patientId = $this->route('patient') ? (is_string($this->route('patient')) ? $this->route('patient') : $this->route('patient')->id) : null;
        $orgId = app()->bound('current_organization_id') ? app('current_organization_id') : null;

        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'date_of_birth' => ['sometimes', 'required', 'date', 'before_or_equal:today'],
            'is_dob_estimated' => ['boolean'],
            'gender' => ['sometimes', 'required', Rule::in(['male', 'female', 'other', 'unknown'])],
            'blood_group' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],

            'national_id' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('patients', 'national_id')
                    ->ignore($patientId)
                    ->where(fn ($q) => $q->where('organization_id', $orgId)->whereNull('deleted_at')),
            ],
            'passport_number' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'alternate_phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed', 'other'])],
            'occupation' => ['nullable', 'string', 'max:100'],
            'preferred_language' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'array'],
            'emergency_contact' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ];
    }
}
