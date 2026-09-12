<?php

namespace App\Domain\Reports\Models;

use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Domain\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyHospitalKpi extends Model
{
    use HasFactory, HasUuids, BelongsToBranch;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'daily_hospital_kpis';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'report_date',
        'total_registered_patients',
        'new_patients_today',
        'total_opd_visits',
        'total_admissions',
        'total_discharges',
        'active_inpatients',
        'total_beds',
        'occupied_beds',
        'occupancy_rate_percentage',
        'average_length_of_stay_days',
        'total_emergency_cases',
        'emergency_resus_cases',
        'total_invoiced_cents',
        'total_collected_cents',
        'total_outstanding_cents',
        'total_prescriptions',
        'total_lab_orders',
        'total_radiology_orders',
        'department_breakdown',
        'payment_mode_breakdown',
        'metadata',
    ];

    protected $casts = [
        'report_date' => 'date',
        'total_registered_patients' => 'integer',
        'new_patients_today' => 'integer',
        'total_opd_visits' => 'integer',
        'total_admissions' => 'integer',
        'total_discharges' => 'integer',
        'active_inpatients' => 'integer',
        'total_beds' => 'integer',
        'occupied_beds' => 'integer',
        'occupancy_rate_percentage' => 'float',
        'average_length_of_stay_days' => 'float',
        'total_emergency_cases' => 'integer',
        'emergency_resus_cases' => 'integer',
        'total_invoiced_cents' => 'integer',
        'total_collected_cents' => 'integer',
        'total_outstanding_cents' => 'integer',
        'total_prescriptions' => 'integer',
        'total_lab_orders' => 'integer',
        'total_radiology_orders' => 'integer',
        'department_breakdown' => 'array',
        'payment_mode_breakdown' => 'array',
        'metadata' => 'array',
    ];

    // Monetary helpers
    public function getTotalInvoicedAttribute(): float
    {
        return round($this->total_invoiced_cents / 100, 2);
    }

    public function getTotalCollectedAttribute(): float
    {
        return round($this->total_collected_cents / 100, 2);
    }

    public function getTotalOutstandingAttribute(): float
    {
        return round($this->total_outstanding_cents / 100, 2);
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
