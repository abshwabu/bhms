<?php

namespace App\Domain\Patient\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterPatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Handled via controller policy or middleware
    }

    /**
     * Sanitize PII inputs before running validation.
     */
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
            // Normalize phone: keep digits and optional leading +
            $phone = preg_replace('/[^\d+]/', '', trim($this->input('phone')));
            $sanitized['phone'] = $phone ?: null;
        }
        if ($this->has('national_id')) {
            $sanitized['national_id'] = strtoupper(trim(strip_tags($this->input('national_id'))));
        }
        if ($this->has('registration_type')) {
            $sanitized['registration_type'] = strtolower(trim($this->input('registration_type')));
        }

        // For emergency registration if last_name is missing, default to Unknown
        if (($this->input('registration_type') === 'emergency') && empty($this->input('last_name'))) {
            $sanitized['last_name'] = 'Unknown';
        }

        $this->merge($sanitized);
    }

    public function rules(): array
    {
        $isEmergency = $this->input('registration_type') === 'emergency';
        $orgId = app()->bound('current_organization_id') ? app('current_organization_id') : null;

        return [
            // Registration Type
            'registration_type' => ['required', Rule::in(['walk_in', 'referral', 'emergency'])],
            'triage_level' => ['nullable', Rule::in(['critical', 'urgent', 'standard', 'non_urgent'])],
            'referral_source' => ['nullable', 'string', 'max:255'],

            // Core Demographics
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => [$isEmergency ? 'nullable' : 'required', 'string', 'max:100'],
            'date_of_birth' => [$isEmergency ? 'nullable' : 'required', 'date', 'before_or_equal:today'],
            'is_dob_estimated' => ['boolean'],
            'gender' => ['required', Rule::in(['male', 'female', 'other', 'unknown'])],
            'blood_group' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],

            // Identification & Contact PII
            'national_id' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('patients', 'national_id')
                    ->where(fn ($q) => $q->where('organization_id', $orgId)->whereNull('deleted_at')),
            ],
            'passport_number' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'alternate_phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', app()->environment('testing') ? 'email:rfc' : 'email:rfc,dns', 'max:255'],
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed', 'other'])],
            'occupation' => ['nullable', 'string', 'max:100'],
            'preferred_language' => ['nullable', 'string', 'max:50'],

            // Address JSON
            'address' => ['nullable', 'array'],
            'address.street' => ['nullable', 'string', 'max:255'],
            'address.city' => ['nullable', 'string', 'max:100'],
            'address.state' => ['nullable', 'string', 'max:100'],
            'address.postal_code' => ['nullable', 'string', 'max:30'],
            'address.country' => ['nullable', 'string', 'max:100'],

            // Emergency Contact JSON
            'emergency_contact' => ['nullable', 'array'],
            'emergency_contact.name' => ['nullable', 'string', 'max:150'],
            'emergency_contact.relationship' => ['nullable', 'string', 'max:50'],
            'emergency_contact.phone' => ['nullable', 'string', 'max:50'],

            // Optional Initial Clinical Notes & Medical History upon intake
            'notes' => ['nullable', 'string', 'max:2000'],
            'initial_history' => ['nullable', 'array'],
            'initial_history.*.category' => ['required', 'string'],
            'initial_history.*.condition_or_procedure' => ['required', 'string', 'max:255'],
            'initial_history.*.diagnosed_date' => ['nullable', 'date'],

            // Optional Initial Allergies
            'initial_allergies' => ['nullable', 'array'],
            'initial_allergies.*.allergen' => ['required', 'string', 'max:255'],
            'initial_allergies.*.reaction' => ['required', 'string', 'max:255'],
            'initial_allergies.*.severity' => ['required', Rule::in(['mild', 'moderate', 'severe', 'life_threatening'])],

            // Optional Initial Insurance
            'initial_insurance' => ['nullable', 'array'],
            'initial_insurance.provider_name' => ['required_with:initial_insurance', 'string', 'max:255'],
            'initial_insurance.policy_number' => ['required_with:initial_insurance', 'string', 'max:100'],
            'initial_insurance.valid_from' => ['nullable', 'date'],
            'initial_insurance.valid_until' => ['nullable', 'date'],
            'initial_insurance.coverage_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],

            // Optional Initial Guardian/Relationship
            'initial_relationship' => ['nullable', 'array'],
            'initial_relationship.related_patient_id' => ['nullable', 'uuid'],
            'initial_relationship.relationship_type' => ['required_with:initial_relationship', 'string'],
            'initial_relationship.external_name' => ['nullable', 'string', 'max:255'],
            'initial_relationship.external_phone' => ['nullable', 'string', 'max:50'],
            'initial_relationship.is_guardian' => ['nullable', 'boolean'],
        ];
    }
}
