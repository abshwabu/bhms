<?php

namespace App\Domain\Compliance\Models;

use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Domain\Shared\Traits\Auditable;
use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientConsent extends Model
{
    use HasFactory, HasUuids, SoftDeletes, BelongsToBranch, Auditable;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'patient_consents';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'patient_id',
        'consent_type',
        'title',
        'purpose',
        'status',
        'granted_at',
        'expires_at',
        'revoked_at',
        'revocation_reason',
        'signature_data',
        'patient_national_id',
        'contact_phone',
        'sensitive_notes',
        'witness_name',
        'witness_user_id',
        'ip_address',
        'user_agent',
        'created_by',
    ];

    /**
     * Acceptance criterion: PII fields are unreadable in raw database dumps without the encryption key.
     * Stored in database as encrypted strings (AES-256-CBC) via APP_KEY.
     */
    protected $casts = [
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'signature_data' => 'encrypted',
        'patient_national_id' => 'encrypted',
        'contact_phone' => 'encrypted',
        'sensitive_notes' => 'encrypted',
    ];

    public function getIsActiveAttribute(): bool
    {
        if ($this->status !== 'granted') {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function revoke(string $reason, ?string $userId = null): self
    {
        $this->update([
            'status' => 'revoked',
            'revoked_at' => Carbon::now(),
            'revocation_reason' => $reason,
        ]);

        return $this;
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function witness(): BelongsTo
    {
        return $this->belongsTo(User::class, 'witness_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
