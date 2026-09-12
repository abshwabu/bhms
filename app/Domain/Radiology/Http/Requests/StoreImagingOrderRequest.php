<?php

namespace App\Domain\Radiology\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreImagingOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'uuid', 'exists:patients,id'],
            'radiology_order_id' => ['nullable', 'uuid', 'exists:radiology_orders,id'],
            'ordering_doctor_id' => ['nullable', 'uuid', 'exists:users,id'],
            'modality' => ['required', 'string', 'max:50'],
            'procedure_name' => ['required', 'string', 'max:255'],
            'procedure_code' => ['nullable', 'string', 'max:50'],
            'body_part' => ['required', 'string', 'max:100'],
            'priority' => ['nullable', 'string', 'in:routine,urgent,stat'],
            'clinical_indication' => ['nullable', 'string', 'max:1000'],
            'patient_preparation' => ['nullable', 'string', 'max:1000'],
            'is_pregnant_or_possible' => ['nullable', 'boolean'],
            'transport_mode' => ['nullable', 'string', 'in:ambulatory,wheelchair,stretcher,portable_bedside'],
            'scheduled_at' => ['nullable', 'date'],
            'scheduled_room' => ['nullable', 'string', 'max:100'],
            'technologist_id' => ['nullable', 'uuid', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
