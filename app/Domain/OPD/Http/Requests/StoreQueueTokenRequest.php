<?php

namespace App\Domain\OPD\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQueueTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_id' => ['required', 'uuid', 'exists:departments,id'],
            'patient_id' => ['required', 'uuid', 'exists:patients,id'],
            'doctor_id' => ['nullable', 'uuid', 'exists:users,id'],
            'appointment_id' => ['nullable', 'uuid', 'exists:appointments,id'],
            'priority' => ['nullable', Rule::in(['normal', 'urgent', 'emergency'])],
        ];
    }
}
