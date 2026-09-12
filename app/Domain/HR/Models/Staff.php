<?php

namespace App\Domain\HR\Models;

use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Staff extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'staff';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'user_id',
        'staff_role_id',
        'employee_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'department',
        'designation',
        'employment_type',
        'joining_date',
        'status',
        'hourly_rate_cents',
        'monthly_salary_cents',
        'bank_details',
        'emergency_contact',
        'notes',
    ];

    protected $casts = [
        'joining_date' => 'date',
        'hourly_rate_cents' => 'integer',
        'monthly_salary_cents' => 'integer',
        'bank_details' => 'array',
        'emergency_contact' => 'array',
    ];

    protected $appends = [
        'full_name',
        'hourly_rate',
        'monthly_salary',
        'has_expiring_credentials',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(StaffRole::class, 'staff_role_id');
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class, 'staff_id')->orderBy('expiry_date', 'asc');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class, 'staff_id')->orderBy('shift_date', 'asc');
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class, 'staff_id')->orderBy('date', 'desc');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'staff_id')->orderBy('start_date', 'desc');
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class, 'staff_id');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getHourlyRateAttribute(): float
    {
        return $this->hourly_rate_cents / 100;
    }

    public function getMonthlySalaryAttribute(): float
    {
        return $this->monthly_salary_cents / 100;
    }

    public function getHasExpiringCredentialsAttribute(): bool
    {
        $today = Carbon::today();
        $cutoff = $today->copy()->addDays(30);

        return $this->credentials()
            ->whereBetween('expiry_date', [$today, $cutoff])
            ->orWhere('expiry_date', '<', $today)
            ->exists();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeDepartment(Builder $query, string $department): Builder
    {
        return $query->where('department', $department);
    }
}
