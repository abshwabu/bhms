<?php

namespace App\Domain\OPD\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AmendSoapNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amendment_reason' => ['required', 'string', 'max:500'],

            'chief_complaint' => ['sometimes', 'required', 'string'],
            'history_of_presenting_illness' => ['nullable', 'string'],
            'review_of_systems' => ['nullable', 'array'],

            'vitals' => ['nullable', 'array'],
            'physical_examination' => ['nullable', 'string'],

            'provisional_diagnosis' => ['sometimes', 'required', 'string', 'max:255'],
            'differential_diagnoses' => ['nullable', 'string'],
            'icd10_codes' => ['nullable', 'array'],

            'treatment_plan' => ['sometimes', 'required', 'string'],
            'prescriptions_advice' => ['nullable', 'string'],
            'orders_requested' => ['nullable', 'string'],
            'follow_up_recommended_date' => ['nullable', 'date'],
            'follow_up_instructions' => ['nullable', 'string'],
        ];
    }
}
