<?php

namespace App\Domain\Reports\Models;

use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorDailyMetric extends Model
{
    use HasFactory, HasUuids, BelongsToBranch;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'doctor_daily_metrics';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'report_date',
        'doctor_id',
        'doctor_name',
        'specialty',
        'department_name',
        'patients_seen_count',
        'appointments_scheduled',
        'appointments_completed',
        'appointments_cancelled',
        'prescriptions_written_count',
        'lab_orders_placed_count',
        'radiology_orders_placed_count',
        'inpatient_admissions_count',
        'revenue_generated_cents',
        'average_consultation_minutes',
    ];

    protected $casts = [
        'report_date' => 'date',
        'patients_seen_count' => 'integer',
        'appointments_scheduled' => 'integer',
        'appointments_completed' => 'integer',
        'appointments_cancelled' => 'integer',
        'prescriptions_written_count' => 'integer',
        'lab_orders_placed_count' => 'integer',
        'radiology_orders_placed_count' => 'integer',
        'inpatient_admissions_count' => 'integer',
        'revenue_generated_cents' => 'integer',
        'average_consultation_minutes' => 'float',
    ];

    public function getRevenueGeneratedAttribute(): float
    {
        return round($this->revenue_generated_cents / 100, 2);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
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
