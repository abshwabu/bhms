<?php

namespace App\Domain\Laboratory\Models;

use App\Domain\Clinical\Models\LabOrder;
use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LabResult extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'lab_results';

    protected $fillable = [
        'report_number',
        'organization_id',
        'branch_id',
        'lab_order_id',
        'lab_test_id',
        'lab_sample_id',
        'patient_id',
        'technician_id',
        'pathologist_id',
        'status',
        'has_abnormal_values',
        'has_critical_values',
        'critical_acknowledged_at',
        'doctor_notified_at',
        'doctor_notified_channel',
        'clinical_remarks',
        'methodology',
        'digital_signature_hash',
        'signed_at',
        'version',
        'is_amended',
        'amended_from_id',
        'amendment_reason',
        'analyzer_device_id',
    ];

    protected $casts = [
        'has_abnormal_values' => 'boolean',
        'has_critical_values' => 'boolean',
        'is_amended' => 'boolean',
        'version' => 'integer',
        'signed_at' => 'datetime',
        'critical_acknowledged_at' => 'datetime',
        'doctor_notified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Enforce lock on signed reports (amendments must create a new versioned entry)
        static::updating(function (LabResult $result) {
            $originalStatus = $result->getOriginal('status');

            if (in_array($originalStatus, ['signed', 'amended'], true)) {
                $dirty = $result->getDirty();
                $restrictedFields = ['clinical_remarks', 'lab_test_id', 'methodology', 'technician_id'];

                foreach ($restrictedFields as $field) {
                    if (array_key_exists($field, $dirty)) {
                        throw new DomainException("Signed laboratory reports are legally immutable. Create a versioned amendment to correct results.");
                    }
                }
            }
        });
    }

    public function labOrder(): BelongsTo
    {
        return $this->belongsTo(LabOrder::class, 'lab_order_id');
    }

    public function labTest(): BelongsTo
    {
        return $this->belongsTo(LabTest::class, 'lab_test_id');
    }

    public function sample(): BelongsTo
    {
        return $this->belongsTo(LabSample::class, 'lab_sample_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function pathologist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pathologist_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(LabResultItem::class, 'lab_result_id');
    }

    public function amendedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'amended_from_id');
    }

    public function amendments(): HasMany
    {
        return $this->hasMany(self::class, 'amended_from_id')->orderBy('version', 'asc');
    }

    /**
     * Digitally sign and seal the laboratory report.
     */
    public function signReport(string $pathologistId): self
    {
        if ($this->status === 'signed') {
            throw new DomainException("Laboratory report is already signed.");
        }

        $itemsSummary = $this->items()->get()->map(fn($i) => "{$i->parameter_name}:{$i->measured_value}")->implode('|');
        $payloadToSign = "{$this->report_number}|{$this->patient_id}|{$pathologistId}|{$itemsSummary}|" . now()->toIso8601String();

        $hash = hash_hmac('sha256', $payloadToSign, config('app.key', 'hms-lab-signing-key'));

        $this->status = 'signed';
        $this->pathologist_id = $pathologistId;
        $this->signed_at = now();
        $this->digital_signature_hash = 'SIG-SHA256-' . substr($hash, 0, 40);
        $this->save();

        // Update overall lab order status to completed and summary
        if ($this->labOrder) {
            $this->labOrder->update([
                'status' => 'completed',
                'completed_at' => now(),
                'abnormal_flags' => $this->has_abnormal_values,
                'results_summary' => $this->clinical_remarks ?: "Laboratory analysis completed and digitally certified by pathologist.",
                'structured_results' => $this->items()->get()->map(fn($item) => [
                    'parameter' => $item->parameter_name,
                    'value' => $item->measured_value,
                    'unit' => $item->unit,
                    'flag' => $item->flag,
                    'reference' => "{$item->reference_low} - {$item->reference_high}",
                ])->all(),
            ]);
        }

        return $this;
    }

    /**
     * Create append-only amendment for a signed report.
     */
    public function amend(array $newItems, string $amendmentReason, string $userId): self
    {
        if ($this->status !== 'signed') {
            throw new DomainException("Only digitally signed laboratory reports can be amended.");
        }

        // Mark current as amended
        $this->is_amended = true;
        $this->status = 'amended';
        $this->save();

        $newReportNumber = $this->report_number . '-REV' . ($this->version + 1);

        $amended = new self([
            'report_number' => $newReportNumber,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'lab_order_id' => $this->lab_order_id,
            'lab_test_id' => $this->lab_test_id,
            'lab_sample_id' => $this->lab_sample_id,
            'patient_id' => $this->patient_id,
            'technician_id' => $userId,
            'status' => 'verified',
            'version' => $this->version + 1,
            'is_amended' => false,
            'amended_from_id' => $this->id,
            'amendment_reason' => $amendmentReason,
            'clinical_remarks' => "Amended report: {$amendmentReason}",
        ]);

        $amended->id = (string) Str::uuid();
        $amended->save();

        return $amended;
    }
}
