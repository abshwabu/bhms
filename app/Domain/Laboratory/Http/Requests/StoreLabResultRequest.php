<?php

namespace App\Domain\Laboratory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lab_order_id' => ['required', 'uuid', 'exists:lab_orders,id'],
            'lab_test_id' => ['nullable', 'uuid', 'exists:lab_tests,id'],
            'lab_sample_id' => ['nullable', 'uuid', 'exists:lab_samples,id'],
            'clinical_remarks' => ['nullable', 'string', 'max:1000'],
            'methodology' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'string', 'in:preliminary,verified'],
            'parameters' => ['required', 'array', 'min:1'],
            'parameters.*.parameter_name' => ['required', 'string', 'max:100'],
            'parameters.*.measured_value' => ['required', 'string', 'max:100'],
            'parameters.*.unit' => ['nullable', 'string', 'max:30'],
            'parameters.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
