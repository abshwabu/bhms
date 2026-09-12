<?php

namespace App\Domain\IPD\Models;

use App\Domain\OPD\Models\Department;
use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ward extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'wards';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'department_id',
        'name',
        'code',
        'ward_type',
        'floor_number',
        'capacity',
        'gender_restriction',
        'daily_rate',
        'is_active',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'daily_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function beds(): HasMany
    {
        return $this->hasMany(Bed::class);
    }

    public function admissions(): HasMany
    {
        return $this->hasMany(Admission::class);
    }

    public function activeAdmissions(): HasMany
    {
        return $this->hasMany(Admission::class)->where('status', 'admitted');
    }
}
