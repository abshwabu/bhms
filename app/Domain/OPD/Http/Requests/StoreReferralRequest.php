<?php

namespace App\Domain\OPD\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'uuid', 'exists:patients,id'],
            'consultation_note_id' => ['nullable', 'uuid', 'exists:consultation_notes,id'],
            'referral_type' => ['required', Rule::in(['internal_department', 'external_facility'])],
            'from_department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'to_department_id' => ['nullable', 'uuid', 'exists:departments,id', 'required_if:referral_type,internal_department'],
            'to_doctor_id' => ['nullable', 'uuid', 'exists:users,id'],
            'external_facility_name' => ['nullable', 'string', 'max:255', 'required_if:referral_type,external_facility'],
            'external_specialist_name' => ['nullable', 'string', 'max:255'],
            'external_contact' => ['nullable', 'string', 'max:100'],
            'priority' => ['nullable', Rule::in(['routine', 'urgent', 'emergency'])],
            'reason_for_referral' => ['required', 'string', 'max:1000'],
            'clinical_summary' => ['nullable', 'string'],
        ];
    }
}
