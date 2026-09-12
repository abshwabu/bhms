<?php

namespace App\Domain\Clinical\Models;

use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EhrRecord extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'ehr_records';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'patient_id',
        'encounter_type',
        'encounter_id',
        'author_id',
        'record_type',
        'category',
        'title',
        'clinical_notes',
        'vitals',
        'status',
        'version',
        'is_amended',
        'amended_from_id',
        'amendment_reason',
        'finalized_at',
        'finalized_by',
    ];

    protected $casts = [
        'clinical_notes' => 'array',
        'vitals' => 'array',
        'is_amended' => 'boolean',
        'version' => 'integer',
        'finalized_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Enforce append-only / immutability for finalized records
        static::updating(function (EhrRecord $record) {
            $originalStatus = $record->getOriginal('status');
            
            // Once finalized or amended, core clinical content cannot be altered in-place
            if (in_array($originalStatus, ['finalized', 'amended'], true)) {
                $dirty = $record->getDirty();
                $restrictedFields = ['clinical_notes', 'vitals', 'title', 'category', 'record_type', 'encounter_id'];
                
                foreach ($restrictedFields as $field) {
                    if (array_key_exists($field, $dirty)) {
                        throw new DomainException("Finalized EHR records are append-only and legally immutable. Create an amendment to update clinical content.");
                    }
                }
            }
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function amendedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'amended_from_id');
    }

    public function amendments(): HasMany
    {
        return $this->hasMany(self::class, 'amended_from_id')->orderBy('version', 'asc');
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(Diagnosis::class, 'ehr_record_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'ehr_record_id');
    }

    public function labOrders(): HasMany
    {
        return $this->hasMany(LabOrder::class, 'ehr_record_id');
    }

    public function radiologyOrders(): HasMany
    {
        return $this->hasMany(RadiologyOrder::class, 'ehr_record_id');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isFinalized(): bool
    {
        return $this->status === 'finalized';
    }

    public function isAmended(): bool
    {
        return $this->is_amended || $this->status === 'amended';
    }

    /**
     * Finalize the draft EHR record, locking it against direct edits.
     */
    public function finalize(string $userId): self
    {
        if ($this->status !== 'draft') {
            throw new DomainException("Only draft EHR records can be finalized.");
        }

        $this->status = 'finalized';
        $this->finalized_at = now();
        $this->finalized_by = $userId;
        $this->save();

        return $this;
    }

    /**
     * Create an append-only amended version of a finalized record.
     */
    public function amend(array $newData, string $amendmentReason, string $userId): self
    {
        if ($this->status !== 'finalized') {
            throw new DomainException("Only finalized EHR records can be amended.");
        }

        // Mark the current record as superseded/amended
        $this->is_amended = true;
        $this->status = 'amended';
        $this->save();

        // Create the next version as a new immutable/draft entry
        $newVersion = new self([
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'patient_id' => $this->patient_id,
            'encounter_type' => $this->encounter_type,
            'encounter_id' => $this->encounter_id,
            'author_id' => $userId,
            'record_type' => $newData['record_type'] ?? $this->record_type,
            'category' => $newData['category'] ?? $this->category,
            'title' => $newData['title'] ?? $this->title,
            'clinical_notes' => $newData['clinical_notes'] ?? $this->clinical_notes,
            'vitals' => $newData['vitals'] ?? $this->vitals,
            'status' => 'finalized',
            'version' => $this->version + 1,
            'is_amended' => false,
            'amended_from_id' => $this->id,
            'amendment_reason' => $amendmentReason,
            'finalized_at' => now(),
            'finalized_by' => $userId,
        ]);

        $newVersion->id = (string) Str::uuid();
        $newVersion->save();

        return $newVersion;
    }
}
