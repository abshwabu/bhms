<?php

namespace App\Domain\IPD\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DischargePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'discharge_type' => ['nullable', Rule::in(['regular', 'ama', 'transfer', 'deceased'])],
            'discharge_condition' => ['nullable', Rule::in(['cured', 'improved', 'stable', 'transferred', 'against_medical_advice', 'deceased'])],
            'primary_diagnosis' => ['nullable', 'string'],
            'secondary_diagnoses' => ['nullable', 'array'],
            'procedures_performed' => ['nullable', 'array'],
            'medications_at_discharge' => ['nullable', 'array'],
            'hospital_course_summary' => ['nullable', 'string'],
            'follow_up_instructions' => ['nullable', 'string'],
            'follow_up_date' => ['nullable', 'date'],
            'is_finalized' => ['nullable', 'boolean'],
        ];
    }
}
