<?php

namespace App\Domain\Clinical\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRadiologyOrderRequest extends FormRequest
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
            'modality' => ['required', 'string', 'max:50'],
            'body_part' => ['required', 'string', 'max:100'],
            'procedure_name' => ['required', 'string', 'max:255'],
            'priority' => ['nullable', 'string', 'in:routine,urgent,stat'],
            'clinical_indication' => ['nullable', 'string', 'max:1000'],
            'transport_required' => ['nullable', 'boolean'],
            'is_pregnant_or_possible' => ['nullable', 'boolean'],
        ];
    }
}
