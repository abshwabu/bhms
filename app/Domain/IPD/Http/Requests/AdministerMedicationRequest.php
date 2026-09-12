<?php

namespace App\Domain\IPD\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdministerMedicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'medication_name' => ['required', 'string', 'max:255'],
            'dosage' => ['required', 'string', 'max:100'],
            'route' => ['nullable', Rule::in(['oral', 'iv', 'im', 'sc', 'topical', 'inhalation'])],
            'scheduled_time' => ['nullable', 'date'],
            'administered_at' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['given', 'missed', 'refused', 'held'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
