<?php

namespace App\Domain\Administration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotificationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
            'code' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:150'],
            'channel' => ['required', 'string', 'in:sms,email,push'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'available_variables' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
