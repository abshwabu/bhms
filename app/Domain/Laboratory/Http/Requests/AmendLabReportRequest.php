<?php

namespace App\Domain\Laboratory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AmendLabReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amendment_reason' => ['required', 'string', 'min:5', 'max:1000'],
            'parameters' => ['required', 'array', 'min:1'],
            'parameters.*.parameter_name' => ['required', 'string', 'max:100'],
            'parameters.*.measured_value' => ['required', 'string', 'max:100'],
            'parameters.*.unit' => ['nullable', 'string', 'max:30'],
            'parameters.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
