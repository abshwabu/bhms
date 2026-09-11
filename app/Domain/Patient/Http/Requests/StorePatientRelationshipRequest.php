<?php

namespace App\Domain\Patient\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientRelationshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'related_patient_id' => ['nullable', 'uuid', 'exists:patients,id'],
            'relationship_type' => [
                'required',
                Rule::in(['parent', 'child', 'spouse', 'guardian', 'sibling', 'caregiver', 'other']),
            ],
            'is_guardian' => ['boolean'],
            'is_emergency_contact' => ['boolean'],
            'is_billing_guarantor' => ['boolean'],

            // If not registered as a patient in system:
            'external_name' => ['required_without:related_patient_id', 'nullable', 'string', 'max:255'],
            'external_phone' => ['nullable', 'string', 'max:50'],
            'external_national_id' => ['nullable', 'string', 'max:100'],
            'external_address' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
