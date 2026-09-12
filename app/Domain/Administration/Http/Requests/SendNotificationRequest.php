<?php

namespace App\Domain\Administration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel' => ['required', 'string', 'in:sms,email,push'],
            'recipient' => ['required', 'string', 'max:255'],
            'event_type' => ['nullable', 'string', 'max:80'],
            'template_code' => ['nullable', 'string', 'max:80'],
            'template_id' => ['nullable', 'uuid', 'exists:notification_templates,id'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'variables' => ['nullable', 'array'],
            'payload' => ['nullable', 'array'],
            'recipient_user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
        ];
    }
}
