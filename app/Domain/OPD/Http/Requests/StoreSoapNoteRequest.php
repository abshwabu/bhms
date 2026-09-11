<?php

namespace App\Domain\OPD\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSoapNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'uuid', 'exists:patients,id'],
            'appointment_id' => ['nullable', 'uuid', 'exists:appointments,id'],

            // Subjective (S)
            'chief_complaint' => ['required', 'string'],
            'history_of_presenting_illness' => ['nullable', 'string'],
            'review_of_systems' => ['nullable', 'array'],

            // Objective (O)
            'vitals' => ['nullable', 'array'],
            'vitals.bp_systolic' => ['nullable', 'numeric'],
            'vitals.bp_diastolic' => ['nullable', 'numeric'],
            'vitals.heart_rate' => ['nullable', 'numeric'],
            'vitals.temperature_c' => ['nullable', 'numeric'],
            'vitals.spo2' => ['nullable', 'numeric'],
            'vitals.respiratory_rate' => ['nullable', 'numeric'],
            'vitals.weight_kg' => ['nullable', 'numeric'],
            'vitals.height_cm' => ['nullable', 'numeric'],
            'physical_examination' => ['nullable', 'string'],

            // Assessment (A)
            'provisional_diagnosis' => ['required', 'string', 'max:255'],
            'differential_diagnoses' => ['nullable', 'string'],
            'icd10_codes' => ['nullable', 'array'],

            // Plan (P)
            'treatment_plan' => ['required', 'string'],
            'prescriptions_advice' => ['nullable', 'string'],
            'orders_requested' => ['nullable', 'string'],
            'diet_and_lifestyle_advice' => ['nullable', 'string'],
            'follow_up_recommended_date' => ['nullable', 'date', 'after_or_equal:today'],
            'follow_up_instructions' => ['nullable', 'string'],

            'sign_off_now' => ['nullable', 'boolean'],
        ];
    }
}
