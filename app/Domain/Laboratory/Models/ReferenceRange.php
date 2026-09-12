<?php

namespace App\Domain\Laboratory\Models;

use App\Domain\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferenceRange extends BaseModel
{
    protected $table = 'reference_ranges';

    protected $fillable = [
        'lab_test_id',
        'parameter_name',
        'unit',
        'gender',
        'age_min_years',
        'age_max_years',
        'normal_low',
        'normal_high',
        'critical_low',
        'critical_high',
        'qualitative_normal',
    ];

    protected $casts = [
        'age_min_years' => 'integer',
        'age_max_years' => 'integer',
        'normal_low' => 'float',
        'normal_high' => 'float',
        'critical_low' => 'float',
        'critical_high' => 'float',
    ];

    public function labTest(): BelongsTo
    {
        return $this->belongsTo(LabTest::class, 'lab_test_id');
    }

    /**
     * Evaluate a measured value against this reference range and determine clinical flag.
     * Flags: 'normal', 'low', 'high', 'critical_low', 'critical_high', 'abnormal'
     */
    public function evaluateValue(string|float|int $value): string
    {
        // 1. Qualitative evaluation (e.g. Negative, Non-reactive)
        if (!empty($this->qualitative_normal)) {
            $normalizedVal = strtolower(trim((string)$value));
            $normalizedNorm = strtolower(trim($this->qualitative_normal));
            return ($normalizedVal === $normalizedNorm) ? 'normal' : 'abnormal';
        }

        // 2. Numeric evaluation
        if (!is_numeric($value)) {
            return 'normal';
        }

        $num = (float) $value;

        // Check Panic / Critical limits first
        if ($this->critical_low !== null && $num < $this->critical_low) {
            return 'critical_low';
        }
        if ($this->critical_high !== null && $num > $this->critical_high) {
            return 'critical_high';
        }

        // Check Normal limits
        if ($this->normal_low !== null && $num < $this->normal_low) {
            return 'low';
        }
        if ($this->normal_high !== null && $num > $this->normal_high) {
            return 'high';
        }

        return 'normal';
    }
}
