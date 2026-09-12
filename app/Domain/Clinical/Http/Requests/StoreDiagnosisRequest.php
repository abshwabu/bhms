<?php

namespace App\Domain\Clinical\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDiagnosisRequest extends FormRequest
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
            'icd10_code' => ['required', 'string', 'max:20'],
            'type' => ['nullable', 'string', 'in:primary,secondary,differential,provisional,working'],
            'severity' => ['nullable', 'string', 'in:mild,moderate,severe'],
            'clinical_status' => ['nullable', 'string', 'in:active,recurrence,remission,resolved'],
            'verification_status' => ['nullable', 'string', 'in:confirmed,provisional,differential,refuted'],
            'onset_date' => ['nullable', 'date'],
            'resolved_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
