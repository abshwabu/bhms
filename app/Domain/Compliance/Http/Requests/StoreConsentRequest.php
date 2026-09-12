<?php

namespace App\Domain\Compliance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'uuid', 'exists:patients,id'],
            'consent_type' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'purpose' => ['required', 'string'],
            'status' => ['nullable', 'string', 'in:granted,pending,expired,revoked'],
            'granted_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'signature_data' => ['nullable', 'string'],
            'patient_national_id' => ['nullable', 'string', 'max:100'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'sensitive_notes' => ['nullable', 'string'],
            'witness_name' => ['nullable', 'string', 'max:150'],
            'witness_user_id' => ['nullable', 'uuid', 'exists:users,id'],
        ];
    }
}
