<?php

namespace App\Domain\IPD\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:20'],
            'ward_type' => ['nullable', Rule::in(['general', 'semi_private', 'private', 'icu', 'hcu', 'isolation', 'maternity', 'pediatric'])],
            'floor_number' => ['nullable', 'string', 'max:20'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'gender_restriction' => ['nullable', Rule::in(['all', 'male_only', 'female_only'])],
            'daily_rate' => ['nullable', 'numeric', 'min:0'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
        ];
    }
}
