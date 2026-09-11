<?php

namespace App\Domain\OPD\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'uuid', 'exists:patients,id'],
            'doctor_id' => ['required', 'uuid', 'exists:users,id'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'string'],
            'end_time' => ['nullable', 'string'],
            'type' => ['nullable', Rule::in(['in_person', 'telemedicine', 'walk_in', 'follow_up'])],
            'appointment_type' => ['nullable', Rule::in(['in_person', 'telemedicine', 'walk_in', 'follow_up'])],
            'reason_for_visit' => ['nullable', 'string', 'max:1000'],
            'parent_appointment_id' => ['nullable', 'uuid', 'exists:appointments,id'],
        ];
    }
}
