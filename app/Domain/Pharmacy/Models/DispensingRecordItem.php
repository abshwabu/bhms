<?php

namespace App\Domain\Pharmacy\Models;

use App\Domain\Clinical\Models\PrescriptionItem;
use App\Domain\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispensingRecordItem extends BaseModel
{
    protected $table = 'dispensing_record_items';

    protected $fillable = [
        'dispensing_record_id',
        'prescription_item_id',
        'drug_id',
        'drug_batch_id',
        'quantity_dispensed',
        'directions',
        'unit_price_cents',
        'total_price_cents',
    ];

    protected $casts = [
        'quantity_dispensed' => 'integer',
        'unit_price_cents' => 'integer',
        'total_price_cents' => 'integer',
    ];

    public function dispensingRecord(): BelongsTo
    {
        return $this->belongsTo(DispensingRecord::class, 'dispensing_record_id');
    }

    public function prescriptionItem(): BelongsTo
    {
        return $this->belongsTo(PrescriptionItem::class, 'prescription_item_id');
    }

    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class, 'drug_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(DrugBatch::class, 'drug_batch_id');
    }
}
