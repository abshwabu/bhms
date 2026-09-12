<?php

namespace App\Domain\Compliance\Services;

use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionService
{
    /**
     * Standard module permissions definition for HMS.
     */
    public const DEFAULT_PERMISSIONS = [
        // Patient & Clinical
        'patient.records.view',
        'patient.records.create',
        'patient.records.update',
        'patient.records.delete',
        'clinical.history.view',
        'clinical.history.modify',
        'clinical.prescriptions.issue',
        // Pharmacy
        'pharmacy.inventory.view',
        'pharmacy.inventory.manage',
        'pharmacy.dispense',
        // Billing & Finance
        'billing.invoices.create',
        'billing.invoices.view',
        'billing.payments.record',
        'billing.discounts.approve',
        'billing.insurance.manage',
        // Inpatient & Emergency
        'inpatient.admissions.manage',
        'emergency.triage.manage',
        'emergency.dispatch.manage',
        // Inventory & HR
        'inventory.assets.manage',
        'hr.staff.manage',
        'hr.roster.manage',
        // Reports & Analytics
        'reports.kpis.view',
        'reports.custom.execute',
        'reports.export',
        // Compliance & Security
        'compliance.audit.view',
        'compliance.roles.manage',
        'compliance.hipaa.audit',
        'compliance.consents.manage',
    ];

    /**
     * Initialize default permissions if not already present in database.
     */
    public function ensureDefaultPermissionsExist(): void
    {
        foreach (self::DEFAULT_PERMISSIONS as $permName) {
            Permission::firstOrCreate([
                'name' => $permName,
                'guard_name' => 'web',
            ]);
        }
    }

    /**
     * List all roles with attached permissions.
     */
    public function getRoles(): Collection
    {
        return Role::with('permissions')->orderBy('name')->get();
    }

    /**
     * List all permissions, optionally grouped by module.
     */
    public function getAllPermissions(): array
    {
        $this->ensureDefaultPermissionsExist();

        $permissions = Permission::orderBy('name')->get();
        $grouped = [];

        foreach ($permissions as $perm) {
            $parts = explode('.', $perm->name);
            $module = $parts[0] ?? 'general';
            $grouped[$module][] = [
                'id' => $perm->id,
                'name' => $perm->name,
                'guard_name' => $perm->guard_name,
            ];
        }

        return [
            'total' => $permissions->count(),
            'grouped' => $grouped,
            'list' => $permissions,
        ];
    }

    /**
     * Create a new role with permissions.
     */
    public function createRole(array $data): Role
    {
        $branchId = $data['branch_id'] ?? (function_exists('getPermissionsTeamId') ? getPermissionsTeamId() : null);

        if (function_exists('setPermissionsTeamId') && $branchId) {
            setPermissionsTeamId($branchId);
        }

        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => $data['guard_name'] ?? 'web',
            'branch_id' => $branchId,
        ]);

        if (! empty($data['permissions'])) {
            $this->ensureDefaultPermissionsExist();
            $role->syncPermissions($data['permissions']);
        }

        return $role->load('permissions');
    }

    /**
     * Update an existing role.
     */
    public function updateRole(Role $role, array $data): Role
    {
        if (in_array($role->name, ['super_admin', 'admin']) && isset($data['name']) && $data['name'] !== $role->name) {
            throw new DomainException('Default administrative roles cannot be renamed.');
        }

        if (isset($data['name'])) {
            $role->name = $data['name'];
        }
        if (array_key_exists('branch_id', $data)) {
            $role->branch_id = $data['branch_id'];
        }
        $role->save();

        if (function_exists('setPermissionsTeamId') && $role->branch_id) {
            setPermissionsTeamId($role->branch_id);
        }

        if (isset($data['permissions'])) {
            $this->ensureDefaultPermissionsExist();
            $role->syncPermissions($data['permissions']);
        }

        return $role->load('permissions');
    }

    /**
     * Delete a role.
     */
    public function deleteRole(Role $role): void
    {
        if (in_array($role->name, ['super_admin', 'admin'])) {
            throw new DomainException('Built-in administrator roles cannot be deleted.');
        }

        $role->delete();
    }

    /**
     * Assign roles to a user.
     */
    public function assignUserRoles(User $user, array $roleNames): User
    {
        if (function_exists('setPermissionsTeamId') && $user->default_branch_id) {
            setPermissionsTeamId($user->default_branch_id);
        }

        $user->syncRoles($roleNames);

        return $user->load('roles.permissions');
    }

    /**
     * Get effective permissions for a user.
     */
    public function getUserPermissions(User $user): array
    {
        if (function_exists('setPermissionsTeamId') && $user->default_branch_id) {
            setPermissionsTeamId($user->default_branch_id);
        }

        $roles = $user->getRoleNames();
        $permissions = $user->getAllPermissions()->pluck('name')->all();

        return [
            'user_id' => $user->id,
            'roles' => $roles,
            'is_admin' => $user->hasRole('admin') || $user->hasRole('super_admin'),
            'permissions' => $permissions,
        ];
    }
}
