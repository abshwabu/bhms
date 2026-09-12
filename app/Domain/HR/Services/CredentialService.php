<?php

namespace App\Domain\HR\Services;

use App\Domain\HR\Models\Credential;
use App\Domain\HR\Models\Staff;
use Carbon\Carbon;
use DomainException;

class CredentialService
{
    /**
     * Record a new medical license, board certification, or professional credential.
     */
    public function recordCredential(Staff $staff, array $data): Credential
    {
        $issueDate = Carbon::parse($data['issue_date']);
        $expiryDate = Carbon::parse($data['expiry_date']);

        if ($expiryDate->lessThanOrEqualTo($issueDate)) {
            throw new DomainException("Credential expiry date must be after the issue date.");
        }

        $today = Carbon::today();
        $status = 'active';
        if ($expiryDate->isPast()) {
            $status = 'expired';
        } elseif ($expiryDate->lessThanOrEqualTo($today->copy()->addDays(30))) {
            $status = 'expiring_soon';
        }

        return Credential::create([
            'organization_id' => $staff->organization_id,
            'branch_id' => $staff->branch_id,
            'staff_id' => $staff->id,
            'credential_type' => $data['credential_type'],
            'title' => $data['title'],
            'license_number' => $data['license_number'],
            'issuing_authority' => $data['issuing_authority'],
            'issue_date' => $issueDate,
            'expiry_date' => $expiryDate,
            'verification_status' => $status,
            'document_url' => $data['document_url'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * HR verification of credential.
     */
    public function verifyCredential(Credential $credential, string $verifierId): Credential
    {
        $credential->verified_by = $verifierId;
        $credential->verified_at = Carbon::now();
        $credential->verification_status = $credential->is_expired
            ? 'expired'
            : ($credential->is_expiring_soon ? 'expiring_soon' : 'active');
        $credential->save();

        return $credential->load(['staff', 'verifier']);
    }

    /**
     * Proactive alerts for expired licenses and credentials nearing renewal deadline.
     * Acceptance criterion: Credential expiry (e.g., license renewal) triggers an alert to HR.
     */
    public function getExpiringCredentialAlerts(?string $branchId = null, int $upcomingDays = 30): array
    {
        $today = Carbon::today();
        $cutoff = $today->copy()->addDays($upcomingDays);

        $query = Credential::query()->with(['staff', 'staff.role']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        // Expired credentials
        $expired = (clone $query)
            ->where('expiry_date', '<', $today)
            ->orderBy('expiry_date', 'asc')
            ->get();

        // Expiring soon within window
        $expiringSoon = (clone $query)
            ->whereBetween('expiry_date', [$today, $cutoff])
            ->orderBy('expiry_date', 'asc')
            ->get();

        return [
            'total_alerts_count' => $expired->count() + $expiringSoon->count(),
            'expired_count' => $expired->count(),
            'expired_credentials' => $expired,
            'expiring_soon_count' => $expiringSoon->count(),
            'expiring_credentials' => $expiringSoon,
            'alert_window_days' => $upcomingDays,
        ];
    }
}
