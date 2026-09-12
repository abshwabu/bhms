<?php

namespace App\Domain\Reports\Models;

use App\Domain\OPD\Models\Department;
use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Domain\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentDailyMetric extends Model
{
    use HasFactory, HasUuids, BelongsToBranch;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'department_daily_metrics';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'report_date',
        'department_id',
        'department_name',
        'patient_count',
        'opd_consultations_count',
        'admissions_count',
        'discharges_count',
        'occupied_beds',
        'total_beds',
        'occupancy_rate',
        'average_length_of_stay_days',
        'lab_orders_count',
        'radiology_orders_count',
        'revenue_cents',
    ];

    protected $casts = [
        'report_date' => 'date',
        'patient_count' => 'integer',
        'opd_consultations_count' => 'integer',
        'admissions_count' => 'integer',
        'discharges_count' => 'integer',
        'occupied_beds' => 'integer',
        'total_beds' => 'integer',
        'occupancy_rate' => 'float',
        'average_length_of_stay_days' => 'float',
        'lab_orders_count' => 'integer',
        'radiology_orders_count' => 'integer',
        'revenue_cents' => 'integer',
    ];

    public function getRevenueAttribute(): float
    {
        return round($this->revenue_cents / 100, 2);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
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
