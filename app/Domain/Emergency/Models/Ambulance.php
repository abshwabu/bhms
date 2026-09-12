<?php

namespace App\Domain\Emergency\Models;

use App\Domain\Shared\Traits\BelongsToBranch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ambulance extends Model
{
    use HasFactory, HasUuids, SoftDeletes, BelongsToBranch;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'ambulances';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'vehicle_number',
        'call_sign',
        'ambulance_type',
        'model',
        'plate_number',
        'status',
        'equipment',
        'current_latitude',
        'current_longitude',
        'heading',
        'speed_kmh',
        'fuel_percentage',
        'last_telemetry_at',
        'assigned_driver_name',
        'assigned_paramedic_name',
        'notes',
    ];

    protected $casts = [
        'equipment' => 'array',
        'current_latitude' => 'float',
        'current_longitude' => 'float',
        'heading' => 'float',
        'speed_kmh' => 'float',
        'fuel_percentage' => 'integer',
        'last_telemetry_at' => 'datetime',
    ];

    protected $appends = [
        'is_available',
        'has_live_gps',
    ];

    public function dispatches(): HasMany
    {
        return $this->hasMany(AmbulanceDispatch::class, 'ambulance_id');
    }

    public function activeDispatch(): HasOne
    {
        return $this->hasOne(AmbulanceDispatch::class, 'ambulance_id')
            ->whereIn('status', ['dispatched', 'en_route_scene', 'at_scene', 'en_route_hospital'])
            ->orderByDesc('dispatched_at');
    }

    public function getIsAvailableAttribute(): bool
    {
        return $this->status === 'available';
    }

    public function getHasLiveGpsAttribute(): bool
    {
        return !is_null($this->current_latitude) && !is_null($this->current_longitude);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available');
    }

    public function scopeDispatched(Builder $query): Builder
    {
        return $query->whereIn('status', ['dispatched', 'en_route_scene', 'at_scene', 'en_route_hospital']);
    }
}
