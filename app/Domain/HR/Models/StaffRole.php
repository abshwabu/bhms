<?php

namespace App\Domain\HR\Models;

use App\Domain\Shared\Models\Organization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffRole extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'staff_roles';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'name',
        'code',
        'department',
        'is_medical',
        'description',
    ];

    protected $casts = [
        'is_medical' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class, 'staff_role_id');
    }
}
