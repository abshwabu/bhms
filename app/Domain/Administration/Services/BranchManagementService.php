<?php

namespace App\Domain\Administration\Services;

use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

class BranchManagementService
{
    /**
     * List all branches for an organization or system.
     */
    public function getBranches(?string $organizationId = null): Collection
    {
        $query = Branch::with('organization')->orderBy('name');

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        return $query->get();
    }

    /**
     * Create a new hospital branch / operating facility.
     */
    public function createBranch(array $data): Branch
    {
        $orgId = $data['organization_id'] ?? (app()->bound('current_organization_id') ? app('current_organization_id') : null);

        if (! $orgId) {
            $org = Organization::first();
            $orgId = $org?->id;
        }

        if (! $orgId) {
            throw new DomainException('An active organization is required to register a branch.');
        }

        return Branch::create([
            'organization_id' => $orgId,
            'name' => $data['name'],
            'code' => strtoupper($data['code']),
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? [],
            'settings' => $data['settings'] ?? [],
            'is_active' => $data['is_active'] ?? true,
            'timezone' => $data['timezone'] ?? 'UTC',
            'currency' => $data['currency'] ?? 'USD',
            'is_main_branch' => $data['is_main_branch'] ?? false,
        ]);
    }

    /**
     * Update branch configuration and settings.
     */
    public function updateBranch(Branch $branch, array $data): Branch
    {
        $branch->update($data);

        return $branch->fresh();
    }

    /**
     * Soft delete branch.
     */
    public function deleteBranch(Branch $branch): void
    {
        if ($branch->is_main_branch) {
            throw new DomainException('The primary main hospital campus cannot be deleted.');
        }

        $branch->delete();
    }

    /**
     * Assign a user to a branch.
     */
    public function assignUserToBranch(string $branchId, string $userId, bool $isDefault = false): void
    {
        $user = User::findOrFail($userId);
        $branch = Branch::findOrFail($branchId);

        $user->branches()->syncWithoutDetaching([
            $branch->id => ['is_default' => $isDefault],
        ]);

        if ($isDefault) {
            $user->update(['default_branch_id' => $branch->id]);
        }
    }

    /**
     * Remove user branch affiliation.
     */
    public function removeUserFromBranch(string $branchId, string $userId): void
    {
        $user = User::findOrFail($userId);
        $user->branches()->detach($branchId);
    }
}
