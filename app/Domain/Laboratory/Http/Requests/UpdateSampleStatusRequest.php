<?php

namespace App\Domain\Laboratory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSampleStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:collected,received,processing,completed,rejected'],
            'collection_site' => ['nullable', 'string', 'max:100'],
            'rejection_reason' => ['required_if:status,rejected', 'nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
