<?php

namespace App\Domain\Administration\Models;

use App\Domain\OPD\Models\Department;
use App\Domain\Shared\Models\BaseModel;
use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Domain\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HospitalService extends BaseModel
{
    use BelongsToBranch;

    protected $table = 'services';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'department_id',
        'code',
        'name',
        'category',
        'description',
        'duration_minutes',
        'base_price_cents',
        'requires_doctor',
        'is_active',
        'preparation_instructions',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'base_price_cents' => 'integer',
        'requires_doctor' => 'boolean',
        'is_active' => 'boolean',
        'preparation_instructions' => 'array',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
