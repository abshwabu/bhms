<?php

namespace App\Domain\OPD\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDoctorScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doctor_id' => ['required', 'uuid', 'exists:users,id'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'schedule_type' => ['required', Rule::in(['recurring', 'specific_date'])],
            'day_of_week' => ['nullable', 'integer', 'between:0,6', 'required_if:schedule_type,recurring'],
            'specific_date' => ['nullable', 'date', 'required_if:schedule_type,specific_date'],
            'start_time' => ['required', 'string'],
            'end_time' => ['required', 'string'],
            'slot_duration_minutes' => ['nullable', 'integer', 'min:5', 'max:120'],
            'max_patients' => ['nullable', 'integer', 'min:1'],
            'is_available' => ['boolean'],
            'room_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
