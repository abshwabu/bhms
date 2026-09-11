<?php

namespace App\Domain\Shared\Traits;

use App\Domain\Shared\Models\AuditLog;
use Illuminate\Support\Str;

trait Auditable
{
    /**
     * Boot the trait to record audit entries on lifecycle events.
     */
    protected static function bootAuditable(): void
    {
        static::created(function ($model) {
            static::recordAuditLog($model, 'created', null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $old = array_intersect_key($model->getOriginal(), $model->getDirty());
            $new = $model->getDirty();
            // Don't audit updated_at timestamp changes alone
            unset($old['updated_at'], $new['updated_at']);

            if (!empty($new)) {
                static::recordAuditLog($model, 'updated', $old, $new);
            }
        });

        static::deleted(function ($model) {
            static::recordAuditLog($model, 'deleted', $model->getOriginal(), null);
        });
    }

    /**
     * Write an audit log entry.
     */
    protected static function recordAuditLog($model, string $event, ?array $oldValues, ?array $newValues): void
    {
        try {
            $user = auth()->user();
            $userId = $user ? $user->id : null;
            $orgId = $model->organization_id ?? (app()->bound('current_organization_id') ? app('current_organization_id') : null);
            $branchId = $model->branch_id ?? (app()->bound('current_branch_id') ? app('current_branch_id') : null);

            AuditLog::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $orgId,
                'branch_id' => $branchId,
                'user_id' => $userId,
                'event' => $event,
                'auditable_type' => get_class($model),
                'auditable_id' => $model->getKey(),
                'old_values' => $oldValues ? json_encode($oldValues) : null,
                'new_values' => $newValues ? json_encode($newValues) : null,
                'ip_address' => request() ? request()->ip() : null,
                'user_agent' => request() ? request()->userAgent() : null,
            ]);
        } catch (\Throwable $e) {
            // Silently log or ignore during migration/seeding if audit tables are not yet ready
            logger()->error('Failed to record audit log: ' . $e->getMessage());
        }
    }
}
