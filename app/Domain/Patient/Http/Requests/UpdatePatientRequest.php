<?php

namespace App\Domain\Patient\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $sanitized = [];

        if ($this->has('first_name')) {
            $sanitized['first_name'] = strip_tags(trim((string) $this->input('first_name')));
        }
        if ($this->has('last_name')) {
            $last = strip_tags(trim((string) $this->input('last_name')));
            $sanitized['last_name'] = $last === '' ? null : $last;
        }
        if ($this->has('middle_name')) {
            $middle = strip_tags(trim((string) $this->input('middle_name')));
            $sanitized['middle_name'] = $middle === '' ? null : $middle;
        }
        if ($this->has('email')) {
            $email = strtolower(trim((string) $this->input('email')));
            $sanitized['email'] = $email === '' ? null : $email;
        }
        if ($this->has('phone')) {
            $phone = preg_replace('/[^\d+]/', '', trim((string) $this->input('phone')));
            $sanitized['phone'] = $phone ?: null;
        }
        if ($this->has('alternate_phone')) {
            $altPhone = preg_replace('/[^\d+]/', '', trim((string) $this->input('alternate_phone')));
            $sanitized['alternate_phone'] = $altPhone ?: null;
        }
        if ($this->has('national_id')) {
            $nid = strtoupper(trim(strip_tags((string) $this->input('national_id'))));
            $sanitized['national_id'] = $nid === '' ? null : $nid;
        }
        if ($this->has('passport_number')) {
            $passport = strtoupper(trim(strip_tags((string) $this->input('passport_number'))));
            $sanitized['passport_number'] = $passport === '' ? null : $passport;
        }
        if ($this->has('blood_group')) {
            $bg = trim((string) $this->input('blood_group'));
            $sanitized['blood_group'] = $bg === '' ? null : $bg;
        }
        if ($this->has('marital_status')) {
            $ms = trim((string) $this->input('marital_status'));
            $sanitized['marital_status'] = $ms === '' ? null : $ms;
        }
        if ($this->has('occupation')) {
            $occ = strip_tags(trim((string) $this->input('occupation')));
            $sanitized['occupation'] = $occ === '' ? null : $occ;
        }
        if ($this->has('preferred_language')) {
            $pl = strip_tags(trim((string) $this->input('preferred_language')));
            $sanitized['preferred_language'] = $pl === '' ? null : $pl;
        }
        if ($this->has('notes')) {
            $notes = strip_tags(trim((string) $this->input('notes')));
            $sanitized['notes'] = $notes === '' ? null : $notes;
        }
        if ($this->has('date_of_birth')) {
            $dob = trim((string) $this->input('date_of_birth'));
            $sanitized['date_of_birth'] = $dob === '' ? null : $dob;
        }

        // Clean empty address object
        if ($this->has('address') && is_array($this->input('address'))) {
            $addr = array_filter($this->input('address'), fn ($val) => is_string($val) && trim($val) !== '');
            if (empty($addr) || (count($addr) === 1 && isset($addr['country']))) {
                $sanitized['address'] = null;
            }
        }

        // Clean empty emergency_contact object
        if ($this->has('emergency_contact') && is_array($this->input('emergency_contact'))) {
            $ec = array_filter($this->input('emergency_contact'), fn ($val) => is_string($val) && trim($val) !== '');
            if (empty($ec)) {
                $sanitized['emergency_contact'] = null;
            }
        }

        $this->merge($sanitized);
    }

    public function rules(): array
    {
        $patientId = $this->route('patient') ? (is_string($this->route('patient')) ? $this->route('patient') : $this->route('patient')->id) : null;
        $orgId = app()->bound('current_organization_id') ? app('current_organization_id') : null;

        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'date_of_birth' => ['sometimes', 'required', 'date', 'before_or_equal:today'],
            'is_dob_estimated' => ['boolean'],
            'gender' => ['sometimes', 'required', Rule::in(['male', 'female', 'other', 'unknown'])],
            'blood_group' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],

            'national_id' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('patients', 'national_id')
                    ->ignore($patientId)
                    ->where(fn ($q) => $q->where('organization_id', $orgId)->whereNull('deleted_at')),
            ],
            'passport_number' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'alternate_phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed', 'other'])],
            'occupation' => ['nullable', 'string', 'max:100'],
            'preferred_language' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'array'],
            'emergency_contact' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ];
    }
}
