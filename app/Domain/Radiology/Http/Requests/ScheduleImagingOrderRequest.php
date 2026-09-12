<?php

namespace App\Domain\Radiology\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleImagingOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scheduled_at' => ['required', 'date'],
            'scheduled_room' => ['nullable', 'string', 'max:100'],
            'technologist_id' => ['nullable', 'uuid', 'exists:users,id'],
        ];
    }
}
