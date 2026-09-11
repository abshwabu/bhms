<?php

namespace App\Domain\Patient\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientAllergyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'allergen' => ['required', 'string', 'max:255'],
            'allergen_type' => ['required', Rule::in(['drug', 'food', 'environmental', 'biological', 'other'])],
            'reaction' => ['required', 'string', 'max:255'],
            'severity' => ['required', Rule::in(['mild', 'moderate', 'severe', 'life_threatening'])],
            'status' => ['nullable', Rule::in(['active', 'suspected', 'resolved'])],
            'diagnosed_at' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
