<?php

namespace App\Domain\Emergency\Models;

use App\Domain\Shared\Traits\BelongsToBranch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmbulanceDispatch extends Model
{
    use HasFactory, HasUuids, SoftDeletes, BelongsToBranch;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'ambulance_dispatches';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'ambulance_id',
        'emergency_case_id',
        'dispatch_number',
        'caller_name',
        'caller_phone',
        'pickup_address',
        'pickup_latitude',
        'pickup_longitude',
        'destination_address',
        'destination_latitude',
        'destination_longitude',
        'priority',
        'nature_of_emergency',
        'status',
        'patient_condition_notes',
        'dispatched_by',
        'dispatched_at',
        'en_route_scene_at',
        'arrived_scene_at',
        'departed_scene_at',
        'arrived_hospital_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'pickup_latitude' => 'float',
        'pickup_longitude' => 'float',
        'destination_latitude' => 'float',
        'destination_longitude' => 'float',
        'dispatched_at' => 'datetime',
        'en_route_scene_at' => 'datetime',
        'arrived_scene_at' => 'datetime',
        'departed_scene_at' => 'datetime',
        'arrived_hospital_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected $appends = [
        'is_active',
        'response_time_minutes',
    ];

    public function ambulance(): BelongsTo
    {
        return $this->belongsTo(Ambulance::class, 'ambulance_id');
    }

    public function emergencyCase(): BelongsTo
    {
        return $this->belongsTo(EmergencyCase::class, 'emergency_case_id');
    }

    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, ['dispatched', 'en_route_scene', 'at_scene', 'en_route_hospital']);
    }

    public function getResponseTimeMinutesAttribute(): ?int
    {
        if (!$this->dispatched_at || !$this->arrived_scene_at) {
            return null;
        }
        return (int) Carbon::parse($this->dispatched_at)->diffInMinutes(Carbon::parse($this->arrived_scene_at));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['dispatched', 'en_route_scene', 'at_scene', 'en_route_hospital']);
    }
}
