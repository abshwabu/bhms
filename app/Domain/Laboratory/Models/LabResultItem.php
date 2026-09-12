<?php

namespace App\Domain\Laboratory\Models;

use App\Domain\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabResultItem extends BaseModel
{
    protected $table = 'lab_result_items';

    protected $fillable = [
        'lab_result_id',
        'reference_range_id',
        'parameter_name',
        'measured_value',
        'numeric_value',
        'unit',
        'reference_low',
        'reference_high',
        'flag',
        'notes',
    ];

    protected $casts = [
        'numeric_value' => 'float',
        'reference_low' => 'float',
        'reference_high' => 'float',
    ];

    public function labResult(): BelongsTo
    {
        return $this->belongsTo(LabResult::class, 'lab_result_id');
    }

    public function referenceRange(): BelongsTo
    {
        return $this->belongsTo(ReferenceRange::class, 'reference_range_id');
    }

    public function isAbnormal(): bool
    {
        return in_array($this->flag, ['low', 'high', 'critical_low', 'critical_high', 'abnormal'], true);
    }

    public function isCritical(): bool
    {
        return in_array($this->flag, ['critical_low', 'critical_high'], true);
    }
}
