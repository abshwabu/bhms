<?php

namespace App\Domain\HR\Models;

use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Credential extends Model
{
    use HasFactory, HasUuids, BelongsToBranch;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'credentials';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'staff_id',
        'credential_type',
        'title',
        'license_number',
        'issuing_authority',
        'issue_date',
        'expiry_date',
        'verification_status',
        'document_url',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'verified_at' => 'datetime',
    ];

    protected $appends = [
        'is_expired',
        'is_expiring_soon',
        'days_until_expiry',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }
        return Carbon::parse($this->expiry_date)->isPast() && !Carbon::parse($this->expiry_date)->isToday();
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }
        $exp = Carbon::parse($this->expiry_date);
        $today = Carbon::today();
        return $exp->greaterThanOrEqualTo($today) && $exp->lessThanOrEqualTo($today->copy()->addDays(30));
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }
        return (int) Carbon::today()->diffInDays(Carbon::parse($this->expiry_date), false);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expiry_date', '<', Carbon::today());
    }

    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        $today = Carbon::today();
        return $query->whereBetween('expiry_date', [$today, $today->copy()->addDays($days)]);
    }
}
