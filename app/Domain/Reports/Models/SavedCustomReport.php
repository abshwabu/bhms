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
use Illuminate\Database\Eloquent\SoftDeletes;

class SavedCustomReport extends Model
{
    use HasFactory, HasUuids, SoftDeletes, BelongsToBranch;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'saved_custom_reports';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'user_id',
        'name',
        'description',
        'entity',
        'selected_fields',
        'filters',
        'sort_field',
        'sort_direction',
        'group_by',
        'date_range_preset',
        'is_public',
    ];

    protected $casts = [
        'selected_fields' => 'array',
        'filters' => 'array',
        'is_public' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
