<?php

namespace App\Domain\Clinical\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEhrRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'uuid', 'exists:patients,id'],
            'encounter_type' => ['nullable', 'string', 'in:opd_appointment,ipd_admission,emergency,walk_in,direct_entry,telemedicine'],
            'encounter_id' => ['nullable', 'uuid'],
            'record_type' => ['nullable', 'string', 'in:consultation_note,progress_note,soap_note,admission_note,discharge_summary,emergency_note,procedure_note'],
            'category' => ['nullable', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'clinical_notes' => ['nullable', 'array'],
            'clinical_notes.chief_complaint' => ['nullable', 'string'],
            'clinical_notes.subjective' => ['nullable', 'string'],
            'clinical_notes.objective' => ['nullable', 'string'],
            'clinical_notes.assessment' => ['nullable', 'string'],
            'clinical_notes.plan' => ['nullable', 'string'],
            'vitals' => ['nullable', 'array'],
            'vitals.bp_systolic' => ['nullable', 'numeric'],
            'vitals.bp_diastolic' => ['nullable', 'numeric'],
            'vitals.heart_rate' => ['nullable', 'numeric'],
            'vitals.temperature_c' => ['nullable', 'numeric'],
            'vitals.respiratory_rate' => ['nullable', 'numeric'],
            'vitals.spo2' => ['nullable', 'numeric'],
            'vitals.bmi' => ['nullable', 'numeric'],
            'status' => ['nullable', 'string', 'in:draft,finalized'],
        ];
    }
}
