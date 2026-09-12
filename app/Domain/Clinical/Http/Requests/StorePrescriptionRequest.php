<?php

namespace App\Domain\Clinical\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'uuid', 'exists:patients,id'],
            'ehr_record_id' => ['nullable', 'uuid', 'exists:ehr_records,id'],
            'appointment_id' => ['nullable', 'uuid'],
            'admission_id' => ['nullable', 'uuid'],
            'notes' => ['nullable', 'string'],
            'override_reason' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:draft,finalized'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medication_name' => ['required', 'string', 'max:255'],
            'items.*.generic_name' => ['nullable', 'string', 'max:255'],
            'items.*.form' => ['nullable', 'string', 'max:50'],
            'items.*.dosage' => ['required', 'string', 'max:100'],
            'items.*.route' => ['nullable', 'string', 'max:50'],
            'items.*.frequency' => ['required', 'string', 'max:100'],
            'items.*.duration_days' => ['required', 'integer', 'min:1', 'max:365'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.instructions' => ['nullable', 'string', 'max:500'],
            'items.*.is_substitution_allowed' => ['nullable', 'boolean'],
        ];
    }
}
