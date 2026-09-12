<?php

namespace App\Domain\SuperAdmin\Services;

use App\Domain\Shared\Models\Organization;
use App\Domain\SuperAdmin\Models\ImpersonationLog;
use App\Models\User;
use DomainException;

class ImpersonationService
{
    /**
     * Start a secure, time-limited impersonation session as a hospital admin.
     */
    public function startImpersonation(
        User $superAdmin,
        string $organizationId,
        string $reason,
        ?string $targetUserId = null,
        ?string $ip = null,
        ?string $userAgent = null
    ): array {
        if (!$superAdmin->is_super_admin) {
            throw new DomainException("Only platform super administrators can initiate support impersonation.");
        }

        $org = Organization::findOrFail($organizationId);

        // Resolve target user: either specified user or the first active admin user of the tenant
        $targetUser = null;
        if ($targetUserId) {
            $targetUser = User::where('organization_id', $organizationId)->findOrFail($targetUserId);
        } else {
            $targetUser = User::where('organization_id', $organizationId)
                ->where('is_active', true)
                ->orderBy('created_at', 'asc')
                ->firstOrFail();
        }

        // Close any prior active impersonation sessions by this super admin
        ImpersonationLog::where('super_admin_id', $superAdmin->id)
            ->where('is_active', true)
            ->update(['is_active' => false, 'ended_at' => now()]);

        // Generate Sanctum access token for the target user, scoped for 2 hours
        $expiresAt = now()->addHours(2);
        $token = $targetUser->createToken('Support Impersonation Token', ['*'], $expiresAt);

        // Record immutable audit log
        $log = ImpersonationLog::create([
            'super_admin_id' => $superAdmin->id,
            'target_user_id' => $targetUser->id,
            'organization_id' => $org->id,
            'token_id' => (string) $token->accessToken->id,
            'reason' => $reason,
            'ip_address' => $ip,
            'user_agent' => substr((string) $userAgent, 0, 255),
            'started_at' => now(),
            'is_active' => true,
        ]);

        return [
            'impersonation_id' => $log->id,
            'token' => $token->plainTextToken,
            'expires_at' => $expiresAt->toIso8601String(),
            'target_user' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
            ],
            'hospital' => [
                'id' => $org->id,
                'name' => $org->name,
                'code' => $org->code,
            ],
            'reason' => $reason,
        ];
    }

    /**
     * Stop and terminate an active impersonation session.
     */
    public function stopImpersonation(string $impersonationLogId): ImpersonationLog
    {
        $log = ImpersonationLog::findOrFail($impersonationLogId);

        if ($log->is_active) {
            $log->update([
                'is_active' => false,
                'ended_at' => now(),
            ]);

            // Revoke the impersonation token if exists
            if ($log->token_id) {
                \DB::table('personal_access_tokens')
                    ->where('id', $log->token_id)
                    ->delete();
            }
        }

        return $log;
    }

    /**
     * List active or past impersonation sessions.
     */
    public function listLogs(?string $organizationId = null)
    {
        return ImpersonationLog::with(['superAdmin:id,name,email', 'targetUser:id,name,email', 'organization:id,name,code'])
            ->when($organizationId, fn($q) => $q->where('organization_id', $organizationId))
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }
}
