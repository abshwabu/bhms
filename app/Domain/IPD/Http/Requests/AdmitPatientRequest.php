<?php

namespace App\Domain\IPD\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdmitPatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'uuid', 'exists:patients,id'],
            'bed_id' => ['required', 'uuid', 'exists:beds,id'],
            'admitting_doctor_id' => ['required', 'uuid', 'exists:users,id'],
            'attending_doctor_id' => ['nullable', 'uuid', 'exists:users,id'],
            'appointment_id' => ['nullable', 'uuid', 'exists:appointments,id'],
            'admission_type' => ['nullable', Rule::in(['emergency', 'elective', 'transfer', 'observation', 'maternity'])],
            'admitted_at' => ['nullable', 'date'],
            'admitting_diagnosis' => ['required', 'string', 'max:1000'],
            'primary_diagnosis' => ['nullable', 'string', 'max:1000'],
            'secondary_diagnoses' => ['nullable', 'array'],
            'procedures_performed' => ['nullable', 'array'],
            'chief_complaint' => ['nullable', 'string'],
            'initial_vitals' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
