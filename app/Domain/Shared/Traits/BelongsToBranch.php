<?php

namespace App\Domain\Shared\Traits;

use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToBranch
{
    /**
     * Boot the trait to attach the global BranchScope and automatically set branch_id.
     */
    protected static function bootBelongsToBranch(): void
    {
        static::addGlobalScope(new BranchScope());

        static::creating(function ($model) {
            if (empty($model->branch_id) && app()->bound('current_branch_id')) {
                $model->branch_id = app('current_branch_id');
            }

            if (empty($model->organization_id) && app()->bound('current_organization_id')) {
                $model->organization_id = app('current_organization_id');
            }
        });
    }

    /**
     * Relationship to Branch.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
