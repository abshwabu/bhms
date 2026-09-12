<?php

namespace App\Domain\Radiology\Models;

use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ImagingReport extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'imaging_reports';

    protected $fillable = [
        'report_number',
        'organization_id',
        'branch_id',
        'imaging_order_id',
        'patient_id',
        'radiologist_id',
        'status',
        'clinical_indication',
        'technique',
        'comparison',
        'findings',
        'impression',
        'recommendations',
        'critical_alert',
        'critical_alert_communicated_at',
        'critical_alert_communicated_to',
        'digital_signature_hash',
        'finalized_at',
        'finalized_by',
        'version',
        'is_amended',
        'amended_from_id',
        'amendment_reason',
    ];

    protected $casts = [
        'critical_alert' => 'boolean',
        'critical_alert_communicated_at' => 'datetime',
        'finalized_at' => 'datetime',
        'is_amended' => 'boolean',
        'version' => 'integer',
    ];

    protected static function booted(): void
    {
        // Enforce legal immutability on finalized reports
        static::updating(function (ImagingReport $report) {
            $originalStatus = $report->getOriginal('status');

            if (in_array($originalStatus, ['finalized', 'amended'], true)) {
                $dirty = $report->getDirty();
                $restrictedFields = ['findings', 'impression', 'technique', 'comparison', 'recommendations', 'radiologist_id'];

                foreach ($restrictedFields as $field) {
                    if (array_key_exists($field, $dirty)) {
                        throw new DomainException("Finalized diagnostic radiology reports are legally immutable. Create a versioned amendment to alter clinical impressions.");
                    }
                }
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ImagingOrder::class, 'imaging_order_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function radiologist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'radiologist_id');
    }

    public function finalizedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ImagingFile::class, 'imaging_report_id');
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
     * Finalize and cryptographically seal the radiology diagnostic report.
     */
    public function finalizeReport(string $radiologistId): self
    {
        if ($this->status === 'finalized') {
            throw new DomainException("Radiology report is already finalized.");
        }

        if (empty(trim((string)$this->impression))) {
            throw new DomainException("Diagnostic Impression is mandatory to finalize an imaging report.");
        }

        $payload = "{$this->report_number}|{$this->imaging_order_id}|{$radiologistId}|" . sha1($this->impression) . "|" . now()->toIso8601String();
        $hash = 'RAD-SHA256-' . substr(hash_hmac('sha256', $payload, config('app.key', 'hms-ris-signing-key')), 0, 40);

        $this->status = 'finalized';
        $this->radiologist_id = $radiologistId;
        $this->finalized_by = $radiologistId;
        $this->finalized_at = now();
        $this->digital_signature_hash = $hash;
        $this->save();

        // Sync with parent imaging order and clinical order
        if ($this->order) {
            $this->order->update(['status' => 'completed']);

            if ($this->order->radiologyOrder) {
                $this->order->radiologyOrder->update([
                    'status' => 'reported',
                    'reported_at' => now(),
                    'radiologist_id' => $radiologistId,
                    'findings' => $this->findings,
                    'impression' => $this->impression,
                ]);
            }
        }

        return $this;
    }

    /**
     * Create an append-only versioned amendment for a finalized report.
     */
    public function amend(array $data, string $reason, string $userId): self
    {
        if ($this->status !== 'finalized') {
            throw new DomainException("Only finalized diagnostic radiology reports can be amended.");
        }

        // Mark current version as amended
        $this->is_amended = true;
        $this->status = 'amended';
        $this->save();

        $newVersionNumber = $this->version + 1;
        $newReportNumber = $this->report_number . "-REV{$newVersionNumber}";

        $amended = new self([
            'report_number' => $newReportNumber,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'imaging_order_id' => $this->imaging_order_id,
            'patient_id' => $this->patient_id,
            'radiologist_id' => $userId,
            'status' => 'draft',
            'clinical_indication' => $data['clinical_indication'] ?? $this->clinical_indication,
            'technique' => $data['technique'] ?? $this->technique,
            'comparison' => $data['comparison'] ?? $this->comparison,
            'findings' => $data['findings'] ?? $this->findings,
            'impression' => $data['impression'] ?? $this->impression,
            'recommendations' => $data['recommendations'] ?? $this->recommendations,
            'critical_alert' => $data['critical_alert'] ?? $this->critical_alert,
            'critical_alert_communicated_at' => $data['critical_alert_communicated_at'] ?? $this->critical_alert_communicated_at,
            'critical_alert_communicated_to' => $data['critical_alert_communicated_to'] ?? $this->critical_alert_communicated_to,
            'version' => $newVersionNumber,
            'is_amended' => false,
            'amended_from_id' => $this->id,
            'amendment_reason' => $reason,
        ]);

        $amended->id = (string) Str::uuid();
        $amended->save();

        return $amended;
    }
}
