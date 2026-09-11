<?php

namespace App\Domain\OPD\Models;

use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\BaseModel;
use App\Models\User;
use App\Domain\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class ConsultationNote extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'consultation_notes';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'appointment_id',
        'patient_id',
        'doctor_id',
        'parent_note_id',
        'version',
        'chief_complaint',
        'history_of_presenting_illness',
        'review_of_systems',
        'vitals',
        'physical_examination',
        'provisional_diagnosis',
        'differential_diagnoses',
        'icd10_codes',
        'treatment_plan',
        'prescriptions_advice',
        'orders_requested',
        'diet_and_lifestyle_advice',
        'follow_up_recommended_date',
        'follow_up_instructions',
        'is_signed_off',
        'signed_off_at',
        'signed_off_by',
        'notes_status',
        'amendment_reason',
    ];

    protected $casts = [
        'version' => 'integer',
        'review_of_systems' => 'array',
        'vitals' => 'array',
        'icd10_codes' => 'array',
        'follow_up_recommended_date' => 'date',
        'is_signed_off' => 'boolean',
        'signed_off_at' => 'datetime',
    ];

    /**
     * Enforce immutability once signed off.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::updating(function (ConsultationNote $note) {
            // If it was already signed off, direct modification of clinical fields is prohibited!
            if ($note->getOriginal('is_signed_off') === true) {
                // Only allow updating amendment_reason or status if strictly managed by versioning
                $clinicalFields = [
                    'chief_complaint',
                    'history_of_presenting_illness',
                    'vitals',
                    'physical_examination',
                    'provisional_diagnosis',
                    'differential_diagnoses',
                    'treatment_plan',
                ];

                foreach ($clinicalFields as $field) {
                    if ($note->isDirty($field)) {
                        throw new InvalidArgumentException(
                            "Cannot update signed-off SOAP consultation note #{$note->id}. Signed-off notes are legally immutable. Create a new amended version instead."
                        );
                    }
                }
            }
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function parentNote(): BelongsTo
    {
        return $this->belongsTo(ConsultationNote::class, 'parent_note_id');
    }

    public function amendments(): HasMany
    {
        return $this->hasMany(ConsultationNote::class, 'parent_note_id');
    }

    public function signedOffByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_off_by');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'consultation_note_id');
    }
}
