<?php

namespace App\Domain\Clinical\Models;

use App\Domain\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionItem extends BaseModel
{
    protected $table = 'prescription_items';

    protected $fillable = [
        'prescription_id',
        'medication_name',
        'generic_name',
        'form',
        'dosage',
        'route',
        'frequency',
        'duration_days',
        'quantity',
        'instructions',
        'is_substitution_allowed',
        'status',
    ];

    protected $casts = [
        'duration_days' => 'integer',
        'quantity' => 'integer',
        'is_substitution_allowed' => 'boolean',
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class, 'prescription_id');
    }
}
