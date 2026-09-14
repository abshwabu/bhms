<?php

namespace App\Domain\Patient\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterPatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Handled via controller policy or middleware
    }

    /**
     * Sanitize PII inputs before running validation.
     */
    protected function prepareForValidation(): void
    {
        $sanitized = [];

        // 1. Registration Type
        $rawRt = strtolower(trim((string) $this->input('registration_type')));
        $registrationType = in_array($rawRt, ['walk_in', 'referral', 'emergency'], true) ? $rawRt : 'walk_in';
        $sanitized['registration_type'] = $registrationType;
        $isEmergency = ($registrationType === 'emergency');

        // 2. First Name - Default safely if blank
        $rawFirst = strip_tags(trim((string) $this->input('first_name')));
        if ($rawFirst !== '') {
            $sanitized['first_name'] = mb_substr($rawFirst, 0, 100);
        } else {
            $sanitized['first_name'] = $isEmergency ? 'Trauma Unknown' : 'Walk-In Patient';
        }

        // 3. Last Name - Default safely if blank
        $rawLast = strip_tags(trim((string) $this->input('last_name')));
        if ($rawLast !== '') {
            $sanitized['last_name'] = mb_substr($rawLast, 0, 100);
        } else {
            $sanitized['last_name'] = $isEmergency ? 'Unknown' : 'Walk-In';
        }

        // 4. Middle Name
        if ($this->has('middle_name')) {
            $middle = strip_tags(trim((string) $this->input('middle_name')));
            $sanitized['middle_name'] = $middle === '' ? null : mb_substr($middle, 0, 100);
        }

        // 5. Gender
        $gen = strtolower(trim((string) $this->input('gender')));
        $sanitized['gender'] = in_array($gen, ['male', 'female', 'other', 'unknown'], true) ? $gen : 'unknown';

        // 6. Date of Birth & is_dob_estimated
        $dobInput = trim((string) $this->input('date_of_birth'));
        $estimated = $this->boolean('is_dob_estimated');

        if ($dobInput === '') {
            $sanitized['date_of_birth'] = now()->subYears(30)->toDateString();
            $sanitized['is_dob_estimated'] = true;
        } else {
            try {
                $carbon = \Carbon\Carbon::parse($dobInput);
                if ($carbon->isFuture()) {
                    $sanitized['date_of_birth'] = now()->toDateString();
                } else {
                    $sanitized['date_of_birth'] = $carbon->toDateString();
                }
                $sanitized['is_dob_estimated'] = $estimated;
            } catch (\Throwable) {
                $sanitized['date_of_birth'] = now()->subYears(30)->toDateString();
                $sanitized['is_dob_estimated'] = true;
            }
        }

        // 7. Blood Group
        $bg = strtoupper(trim((string) $this->input('blood_group')));
        $validBgs = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        $sanitized['blood_group'] = in_array($bg, $validBgs, true) ? $bg : null;

        // 8. Email
        $email = strtolower(trim((string) $this->input('email')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $sanitized['email'] = null;
        } else {
            $sanitized['email'] = mb_substr($email, 0, 255);
        }

        // 9. Phone & Alternate Phone
        if ($this->has('phone')) {
            $phone = preg_replace('/[^\d+]/', '', trim((string) $this->input('phone')));
            $sanitized['phone'] = $phone ? mb_substr($phone, 0, 50) : null;
        }
        if ($this->has('alternate_phone')) {
            $altPhone = preg_replace('/[^\d+]/', '', trim((string) $this->input('alternate_phone')));
            $sanitized['alternate_phone'] = $altPhone ? mb_substr($altPhone, 0, 50) : null;
        }

        // 10. National ID - Deduplicate if already taken in this org
        if ($this->has('national_id')) {
            $nid = strtoupper(trim(strip_tags((string) $this->input('national_id'))));
            if ($nid === '') {
                $sanitized['national_id'] = null;
            } else {
                $orgId = app()->bound('current_organization_id') 
                    ? app('current_organization_id') 
                    : (auth()->user()?->organization_id ?? null);
                $exists = \App\Domain\Patient\Models\Patient::where('organization_id', $orgId)
                    ->where('national_id', $nid)
                    ->whereNull('deleted_at')
                    ->exists();
                if ($exists) {
                    $sanitized['national_id'] = mb_substr($nid, 0, 90) . '-' . strtoupper(substr((string) \Illuminate\Support\Str::uuid(), 0, 4));
                } else {
                    $sanitized['national_id'] = mb_substr($nid, 0, 100);
                }
            }
        }

        // 11. Passport Number
        if ($this->has('passport_number')) {
            $passport = strtoupper(trim(strip_tags((string) $this->input('passport_number'))));
            $sanitized['passport_number'] = $passport === '' ? null : mb_substr($passport, 0, 100);
        }

        // 12. Referral Source & Triage Level
        if ($this->has('referral_source')) {
            $ref = strip_tags(trim((string) $this->input('referral_source')));
            $sanitized['referral_source'] = $ref === '' ? null : mb_substr($ref, 0, 255);
        }
        if ($this->has('triage_level')) {
            $tl = strtolower(trim((string) $this->input('triage_level')));
            $sanitized['triage_level'] = in_array($tl, ['critical', 'urgent', 'standard', 'non_urgent'], true) ? $tl : null;
        }

        // 13. Demographics Details
        if ($this->has('marital_status')) {
            $ms = strtolower(trim((string) $this->input('marital_status')));
            $sanitized['marital_status'] = in_array($ms, ['single', 'married', 'divorced', 'widowed', 'other'], true) ? $ms : null;
        }
        if ($this->has('occupation')) {
            $occ = strip_tags(trim((string) $this->input('occupation')));
            $sanitized['occupation'] = $occ === '' ? null : mb_substr($occ, 0, 100);
        }
        if ($this->has('preferred_language')) {
            $pl = strip_tags(trim((string) $this->input('preferred_language')));
            $sanitized['preferred_language'] = $pl === '' ? null : mb_substr($pl, 0, 50);
        }
        if ($this->has('notes')) {
            $notes = strip_tags(trim((string) $this->input('notes')));
            $sanitized['notes'] = $notes === '' ? null : mb_substr($notes, 0, 2000);
        }

        // 14. Clean empty address object
        if ($this->has('address') && is_array($this->input('address'))) {
            $addr = array_filter($this->input('address'), fn ($val) => is_string($val) && trim($val) !== '');
            if (empty($addr) || (count($addr) === 1 && isset($addr['country']))) {
                $sanitized['address'] = null;
            }
        }

        // 15. Clean empty emergency_contact object
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
        $orgId = app()->bound('current_organization_id') 
            ? app('current_organization_id') 
            : (auth()->user()?->organization_id ?? null);

        return [
            // Registration Type
            'registration_type' => ['required', Rule::in(['walk_in', 'referral', 'emergency'])],
            'triage_level' => ['nullable', Rule::in(['critical', 'urgent', 'standard', 'non_urgent'])],
            'referral_source' => ['nullable', 'string', 'max:255'],

            // Core Demographics
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'is_dob_estimated' => ['boolean'],
            'gender' => ['required', Rule::in(['male', 'female', 'other', 'unknown'])],
            'blood_group' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],

            // Identification & Contact PII
            'national_id' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('patients', 'national_id')
                    ->where(fn ($q) => $q->where('organization_id', $orgId)->whereNull('deleted_at')),
            ],
            'passport_number' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'alternate_phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed', 'other'])],
            'occupation' => ['nullable', 'string', 'max:100'],
            'preferred_language' => ['nullable', 'string', 'max:50'],

            // Address JSON
            'address' => ['nullable', 'array'],
            'address.street' => ['nullable', 'string', 'max:255'],
            'address.city' => ['nullable', 'string', 'max:100'],
            'address.state' => ['nullable', 'string', 'max:100'],
            'address.postal_code' => ['nullable', 'string', 'max:30'],
            'address.country' => ['nullable', 'string', 'max:100'],

            // Emergency Contact JSON
            'emergency_contact' => ['nullable', 'array'],
            'emergency_contact.name' => ['nullable', 'string', 'max:150'],
            'emergency_contact.relationship' => ['nullable', 'string', 'max:50'],
            'emergency_contact.phone' => ['nullable', 'string', 'max:50'],

            // Optional Initial Clinical Notes & Medical History upon intake
            'notes' => ['nullable', 'string', 'max:2000'],
            'initial_history' => ['nullable', 'array'],
            'initial_history.*.category' => ['required', 'string'],
            'initial_history.*.condition_or_procedure' => ['required', 'string', 'max:255'],
            'initial_history.*.diagnosed_date' => ['nullable', 'date'],

            // Optional Initial Allergies
            'initial_allergies' => ['nullable', 'array'],
            'initial_allergies.*.allergen' => ['required', 'string', 'max:255'],
            'initial_allergies.*.reaction' => ['required', 'string', 'max:255'],
            'initial_allergies.*.severity' => ['required', Rule::in(['mild', 'moderate', 'severe', 'life_threatening'])],

            // Optional Initial Insurance
            'initial_insurance' => ['nullable', 'array'],
            'initial_insurance.provider_name' => ['required_with:initial_insurance', 'string', 'max:255'],
            'initial_insurance.policy_number' => ['required_with:initial_insurance', 'string', 'max:100'],
            'initial_insurance.valid_from' => ['nullable', 'date'],
            'initial_insurance.valid_until' => ['nullable', 'date'],
            'initial_insurance.coverage_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],

            // Optional Initial Guardian/Relationship
            'initial_relationship' => ['nullable', 'array'],
            'initial_relationship.related_patient_id' => ['nullable', 'uuid'],
            'initial_relationship.relationship_type' => ['required_with:initial_relationship', 'string'],
            'initial_relationship.external_name' => ['nullable', 'string', 'max:255'],
            'initial_relationship.external_phone' => ['nullable', 'string', 'max:50'],
            'initial_relationship.is_guardian' => ['nullable', 'boolean'],
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        \Illuminate\Support\Facades\Log::warning('[RegisterPatientRequest Validation Failed]', [
            'errors' => $validator->errors()->toArray(),
            'input' => $this->all(),
        ]);

        parent::failedValidation($validator);
    }
}
