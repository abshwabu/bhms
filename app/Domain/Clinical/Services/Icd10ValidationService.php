<?php

namespace App\Domain\Clinical\Services;

use App\Domain\Clinical\Models\Icd10Code;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class Icd10ValidationService
{
    /**
     * Standard regex pattern for ICD-10-CM codes.
     * Starts with a letter, followed by 2 digits, optionally a dot and 1 to 4 alphanumeric chars.
     */
    public const ICD10_REGEX = '/^[A-Z][0-9]{2}(\.[0-9A-Z]{1,4})?$/i';

    /**
     * Validate an ICD-10 code against the standard master list.
     * Throws ValidationException if invalid.
     *
     * @param string $code
     * @return Icd10Code
     * @throws ValidationException
     */
    public function validate(string $code): Icd10Code
    {
        $normalizedCode = strtoupper(trim($code));

        if (!preg_match(self::ICD10_REGEX, $normalizedCode)) {
            throw ValidationException::withMessages([
                'icd10_code' => ["The ICD-10 code '{$code}' does not match standard ICD-10 syntax (e.g., E11.9, I10, J45.909)."]
            ]);
        }

        $icdRecord = Icd10Code::where('code', $normalizedCode)
            ->where('is_active', true)
            ->first();

        if (!$icdRecord) {
            throw ValidationException::withMessages([
                'icd10_code' => ["The code '{$normalizedCode}' is not recognized in the standard clinical ICD-10 master code list."]
            ]);
        }

        return $icdRecord;
    }

    /**
     * Search ICD-10 codes for clinician autocomplete.
     */
    public function search(string $query, int $limit = 20): Collection
    {
        return Icd10Code::active()
            ->search($query)
            ->limit($limit)
            ->get();
    }
}
