<?php

namespace App\Domain\IPD\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LogVitalsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recorded_at' => ['nullable', 'date'],
            'bp_systolic' => ['nullable', 'numeric', 'min:30', 'max:300'],
            'bp_diastolic' => ['nullable', 'numeric', 'min:10', 'max:200'],
            'heart_rate' => ['nullable', 'numeric', 'min:20', 'max:300'],
            'respiratory_rate' => ['nullable', 'numeric', 'min:4', 'max:80'],
            'temperature_c' => ['nullable', 'numeric', 'min:30', 'max:45'],
            'spo2' => ['nullable', 'numeric', 'min:50', 'max:100'],
            'blood_glucose_mg_dl' => ['nullable', 'numeric', 'min:10', 'max:1000'],
            'pain_score' => ['nullable', 'integer', 'min:0', 'max:10'],
            'consciousness_level' => ['nullable', Rule::in(['alert', 'verbal', 'voice', 'pain', 'unresponsive'])],
            'urine_output_ml' => ['nullable', 'numeric', 'min:0'],
            'nursing_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
