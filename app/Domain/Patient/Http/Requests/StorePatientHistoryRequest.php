<?php

namespace App\Domain\Patient\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => [
                'required',
                Rule::in(['chronic_condition', 'past_illness', 'surgical_history', 'family_history', 'social_history', 'medication_history']),
            ],
            'condition_or_procedure' => ['required', 'string', 'max:255'],
            'icd10_code' => ['nullable', 'string', 'max:20'],
            'diagnosed_date' => ['nullable', 'date', 'before_or_equal:today'],
            'status' => ['nullable', Rule::in(['active', 'resolved', 'managed', 'recurrent'])],
            'severity' => ['nullable', Rule::in(['mild', 'moderate', 'severe'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
