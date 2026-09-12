<?php

namespace App\Domain\Laboratory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SignLabReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clinical_remarks' => ['nullable', 'string', 'max:1000'],
            'pathologist_id' => ['nullable', 'uuid', 'exists:users,id'],
        ];
    }
}
