<?php

namespace App\Domain\Laboratory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Hl7MessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hl7_message' => ['required_without:astm_message', 'nullable', 'string'],
            'astm_message' => ['required_without:hl7_message', 'nullable', 'string'],
            'device_id' => ['nullable', 'string', 'max:100'],
        ];
    }
}
