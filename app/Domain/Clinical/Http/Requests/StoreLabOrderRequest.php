<?php

namespace App\Domain\Clinical\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabOrderRequest extends FormRequest
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
            'test_type' => ['required', 'string', 'max:150'],
            'test_code' => ['nullable', 'string', 'max:50'],
            'priority' => ['nullable', 'string', 'in:routine,urgent,stat'],
            'clinical_indication' => ['nullable', 'string', 'max:1000'],
            'special_instructions' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
