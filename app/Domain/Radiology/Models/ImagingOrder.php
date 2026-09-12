<?php

namespace App\Domain\Radiology\Models;

use App\Domain\Clinical\Models\RadiologyOrder;
use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ImagingOrder extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'imaging_orders';

    protected $fillable = [
        'accession_number',
        'organization_id',
        'branch_id',
        'patient_id',
        'radiology_order_id',
        'ordering_doctor_id',
        'technologist_id',
        'modality',
        'procedure_code',
        'procedure_name',
        'body_part',
        'priority',
        'clinical_indication',
        'patient_preparation',
        'is_pregnant_or_possible',
        'transport_mode',
        'status',
        'scheduled_at',
        'scheduled_room',
        'started_at',
        'completed_at',
        'dicom_study_uid',
        'pacs_status',
        'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'is_pregnant_or_possible' => 'boolean',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function radiologyOrder(): BelongsTo
    {
        return $this->belongsTo(RadiologyOrder::class, 'radiology_order_id');
    }

    public function orderingDoctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordering_doctor_id');
    }

    public function technologist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technologist_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ImagingReport::class, 'imaging_order_id')->orderBy('version', 'desc');
    }

    public function latestReport(): HasOne
    {
        return $this->hasOne(ImagingReport::class, 'imaging_order_id')->latestOfMany('version');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ImagingFile::class, 'imaging_order_id');
    }

    /**
     * Schedule the imaging procedure slot and room.
     */
    public function schedule(Carbon|string $scheduledAt, ?string $room = null, ?string $technologistId = null): self
    {
        if ($this->status === 'completed' || $this->status === 'cancelled') {
            throw new DomainException("Cannot reschedule an imaging order with status '{$this->status}'.");
        }

        $this->update([
            'status' => 'scheduled',
            'scheduled_at' => $scheduledAt,
            'scheduled_room' => $room ?? $this->scheduled_room,
            'technologist_id' => $technologistId ?? $this->technologist_id,
        ]);

        if ($this->radiologyOrder && $this->radiologyOrder->status === 'ordered') {
            $this->radiologyOrder->update(['status' => 'scheduled']);
        }

        return $this;
    }

    /**
     * Mark procedure as started / in progress.
     */
    public function markInProgress(?string $technologistId = null): self
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
            'technologist_id' => $technologistId ?? $this->technologist_id,
        ]);

        return $this;
    }

    /**
     * Mark procedure as completed / acquired.
     */
    public function markCompleted(): self
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'pacs_status' => 'available',
        ]);

        if ($this->radiologyOrder) {
            $this->radiologyOrder->update([
                'status' => 'performed',
                'performed_at' => now(),
            ]);
        }

        return $this;
    }

    public function scopeModality(Builder $query, string $modality): Builder
    {
        return $query->where('modality', $modality);
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
}
