<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipment extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'equipment';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'asset_tag',
        'name',
        'category',
        'model_number',
        'serial_number',
        'manufacturer',
        'vendor_id',
        'department',
        'room_location',
        'purchase_date',
        'purchase_cost_cents',
        'warranty_expiry_date',
        'status',
        'criticality',
        'maintenance_frequency_days',
        'last_maintenance_date',
        'next_maintenance_date',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_expiry_date' => 'date',
        'last_maintenance_date' => 'date',
        'next_maintenance_date' => 'date',
        'purchase_cost_cents' => 'integer',
        'maintenance_frequency_days' => 'integer',
    ];

    protected $appends = [
        'purchase_cost',
        'is_maintenance_overdue',
        'is_maintenance_due_soon',
        'is_warranty_expired',
        'days_until_next_maintenance',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class, 'equipment_id')->orderBy('scheduled_date', 'desc');
    }

    public function getPurchaseCostAttribute(): float
    {
        return $this->purchase_cost_cents / 100;
    }

    public function getIsMaintenanceOverdueAttribute(): bool
    {
        if (!$this->next_maintenance_date || $this->status === 'decommissioned') {
            return false;
        }
        return Carbon::parse($this->next_maintenance_date)->isPast() && !Carbon::parse($this->next_maintenance_date)->isToday();
    }

    public function getIsMaintenanceDueSoonAttribute(): bool
    {
        if (!$this->next_maintenance_date || $this->status === 'decommissioned') {
            return false;
        }
        $next = Carbon::parse($this->next_maintenance_date);
        $today = Carbon::today();
        return $next->greaterThanOrEqualTo($today) && $next->lessThanOrEqualTo($today->copy()->addDays(14));
    }

    public function getIsWarrantyExpiredAttribute(): bool
    {
        if (!$this->warranty_expiry_date) {
            return false;
        }
        return Carbon::parse($this->warranty_expiry_date)->isPast();
    }

    public function getDaysUntilNextMaintenanceAttribute(): ?int
    {
        if (!$this->next_maintenance_date) {
            return null;
        }
        return (int) Carbon::today()->diffInDays(Carbon::parse($this->next_maintenance_date), false);
    }

    public function scopeOperational(Builder $query): Builder
    {
        return $query->where('status', 'operational');
    }

    public function scopeMaintenanceOverdue(Builder $query): Builder
    {
        return $query->where('next_maintenance_date', '<', Carbon::today())
            ->where('status', '!=', 'decommissioned');
    }

    public function scopeMaintenanceDueSoon(Builder $query, int $days = 14): Builder
    {
        $today = Carbon::today();
        $cutoff = $today->copy()->addDays($days);
        return $query->whereBetween('next_maintenance_date', [$today, $cutoff])
            ->where('status', '!=', 'decommissioned');
    }
}
