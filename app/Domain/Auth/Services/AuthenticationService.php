<?php

namespace App\Domain\Auth\Services;

use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AuthenticationService
{
    /**
     * Attempt login with credentials and return user context and token.
     */
    public function login(string $email, string $password): array
    {
        $this->ensureDemoUsersExist();

        $user = User::where('email', strtolower(trim($email)))->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['This account has been deactivated. Please contact your hospital administrator.'],
            ]);
        }

        // Check if hospital tenant organization is suspended
        if ($user->organization && $user->organization->subscription_status === 'suspended' && !$user->is_super_admin) {
            $reason = $user->organization->suspension_reason ?: 'Subscription inactive or delinquent.';
            throw ValidationException::withMessages([
                'email' => ["Access suspended for {$user->organization->name}: {$reason}"],
            ]);
        }

        // Generate Sanctum token
        $token = $user->createToken('hms-portal-session')->plainTextToken;

        return $this->formatUserData($user, $token);
    }

    /**
     * Format comprehensive user profile including hospital, branch, roles, and permissions.
     */
    public function formatUserData(User $user, ?string $token = null, ?string $branchId = null): array
    {
        $targetBranchId = $branchId ?: $user->default_branch_id;

        if (function_exists('setPermissionsTeamId') && $targetBranchId) {
            setPermissionsTeamId($targetBranchId);
        }

        $user->unsetRelation('roles')->unsetRelation('permissions');
        $user->loadMissing(['organization', 'defaultBranch', 'branches', 'roles.permissions']);

        $roles = $user->roles->pluck('name')->all();

        // Fallback check across user's assigned branches if roles is empty
        if (empty($roles) && !$user->is_super_admin && $user->branches->isNotEmpty()) {
            foreach ($user->branches as $branch) {
                if (function_exists('setPermissionsTeamId')) {
                    setPermissionsTeamId($branch->id);
                }
                $user->unsetRelation('roles')->unsetRelation('permissions');
                $branchRoles = $user->roles->pluck('name')->all();
                if (!empty($branchRoles)) {
                    $roles = array_values(array_unique(array_merge($roles, $branchRoles)));
                }
            }
            // Reset team ID to target branch
            if (function_exists('setPermissionsTeamId') && $targetBranchId) {
                setPermissionsTeamId($targetBranchId);
            }
            $user->unsetRelation('roles')->unsetRelation('permissions');
        }

        if ($user->is_super_admin && !in_array('super_admin', $roles)) {
            $roles[] = 'super_admin';
        }

        $permissions = $user->getAllPermissions()->pluck('name')->all();

        $data = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'employee_id' => $user->employee_id,
                'is_super_admin' => (bool) $user->is_super_admin,
                'roles' => $roles,
                'primary_role' => $this->resolvePrimaryRole($user, $roles),
                'permissions' => $permissions,
            ],
            'organization' => $user->organization ? [
                'id' => $user->organization->id,
                'name' => $user->organization->name,
                'code' => $user->organization->code,
                'plan_tier' => $user->organization->plan_tier ?? 'community',
                'is_active' => (bool) $user->organization->is_active,
                'subscription_status' => $user->organization->subscription_status ?? 'active',
            ] : null,
            'default_branch' => $user->defaultBranch ? [
                'id' => $user->defaultBranch->id,
                'name' => $user->defaultBranch->name,
                'code' => $user->defaultBranch->code,
            ] : null,
            'accessible_branches' => $user->branches->map(fn($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'code' => $b->code,
            ])->all(),
        ];

        if ($token) {
            $data['token'] = $token;
        }

        return $data;
    }

    /**
     * Determine user-friendly primary role display name.
     */
    protected function resolvePrimaryRole(User $user, array $roles): string
    {
        if ($user->is_super_admin || in_array('super_admin', $roles)) {
            return 'Super Admin';
        }
        if (in_array('hospital_admin', $roles) || in_array('admin', $roles)) {
            return 'Hospital Administrator';
        }
        if (in_array('doctor', $roles)) {
            return 'Doctor / Clinician';
        }
        if (in_array('nurse', $roles)) {
            return 'Inpatient Nurse';
        }
        if (in_array('pharmacist', $roles)) {
            return 'Chief Pharmacist';
        }
        if (in_array('billing_officer', $roles)) {
            return 'Billing & Claims Officer';
        }
        if (in_array('receptionist', $roles)) {
            return 'Reception / OPD Registrar';
        }
        if (in_array('lab_technician', $roles)) {
            return 'Lab Specialist';
        }

        return !empty($roles) ? ucwords(str_replace('_', ' ', $roles[0])) : 'Hospital Staff';
    }

    /**
     * Ensure baseline hospital organization, branch, roles, and demo accounts exist.
     */
    public function ensureDemoUsersExist(): void
    {
        // 1. Organization
        $org = Organization::firstOrCreate(
            ['code' => 'MHS'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Metro Health System',
                'tax_number' => 'TAX-MHS-99882',
                'plan_tier' => 'enterprise',
                'subscription_status' => 'active',
                'settings' => ['currency' => 'ETB', 'timezone' => 'UTC'],
                'is_active' => true,
            ]
        );

        // 2. Main Campus Branch
        $branch = Branch::firstOrCreate(
            ['organization_id' => $org->id, 'code' => 'MAIN'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Metro General Hospital (Main Campus)',
                'phone' => '+1-555-0100',
                'email' => 'main@metrohealth.org',
                'is_active' => true,
            ]
        );

        // North Branch
        $northBranch = Branch::firstOrCreate(
            ['organization_id' => $org->id, 'code' => 'NORTH'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Metro North Outpatient Clinic',
                'phone' => '+1-555-0200',
                'email' => 'north@metrohealth.org',
                'is_active' => true,
            ]
        );

        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId($branch->id);
        }

        // 3. Demo Roles
        $roles = [
            'super_admin',
            'hospital_admin',
            'doctor',
            'nurse',
            'pharmacist',
            'billing_officer',
            'receptionist',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
                'branch_id' => $branch->id,
            ]);
        }

        // 4. Demo Accounts
        $accounts = [
            [
                'email' => 'superadmin@hms.local',
                'name' => 'Alex Thorne (Vendor Operator)',
                'employee_id' => 'EMP-VEND-001',
                'role' => 'super_admin',
                'is_super_admin' => true,
            ],
            [
                'email' => 'admin@hms.local',
                'name' => 'Dr. Arthur Sterling (Hospital Director)',
                'employee_id' => 'EMP-ADM-001',
                'role' => 'hospital_admin',
                'is_super_admin' => false,
            ],
            [
                'email' => 'doctor@hms.local',
                'name' => 'Dr. Eleanor Vance, MD',
                'employee_id' => 'EMP-DOC-001',
                'role' => 'doctor',
                'is_super_admin' => false,
            ],
            [
                'email' => 'nurse@hms.local',
                'name' => 'Sister Clara Oswald, RN',
                'employee_id' => 'EMP-NUR-001',
                'role' => 'nurse',
                'is_super_admin' => false,
            ],
            [
                'email' => 'pharmacist@hms.local',
                'name' => 'Marcus Holloway, PharmD',
                'employee_id' => 'EMP-PHARM-001',
                'role' => 'pharmacist',
                'is_super_admin' => false,
            ],
            [
                'email' => 'billing@hms.local',
                'name' => 'Jennifer Blake (Finance Lead)',
                'employee_id' => 'EMP-BILL-001',
                'role' => 'billing_officer',
                'is_super_admin' => false,
            ],
            [
                'email' => 'receptionist@hms.local',
                'name' => 'Sarah Connor (OPD Registrar)',
                'employee_id' => 'EMP-REC-001',
                'role' => 'receptionist',
                'is_super_admin' => false,
            ],
        ];

        foreach ($accounts as $acc) {
            $user = User::firstOrCreate(
                ['email' => $acc['email']],
                [
                    'id' => (string) Str::uuid(),
                    'organization_id' => $org->id,
                    'default_branch_id' => $branch->id,
                    'employee_id' => $acc['employee_id'],
                    'name' => $acc['name'],
                    'phone' => '+1-555-0199',
                    'password' => Hash::make('password123'),
                    'is_active' => true,
                    'is_patient' => false,
                    'is_super_admin' => $acc['is_super_admin'],
                ]
            );

            // Ensure attributes are up to date
            if ($user->is_super_admin !== $acc['is_super_admin']) {
                $user->update(['is_super_admin' => $acc['is_super_admin']]);
            }

            $user->branches()->syncWithoutDetaching([
                $branch->id => ['is_default' => true],
                $northBranch->id => ['is_default' => false],
            ]);

            if (!$user->hasRole($acc['role'])) {
                $user->assignRole($acc['role']);
            }
        }
    }

    /**
     * Get quick demo account profiles for frontend switcher.
     */
    public function getDemoAccounts(): array
    {
        $this->ensureDemoUsersExist();

        return [
            [
                'role_key' => 'hospital_admin',
                'role_label' => 'Hospital Admin',
                'email' => 'admin@hms.local',
                'password' => 'password123',
                'name' => 'Dr. Arthur Sterling',
                'tag' => 'Hospital Director',
                'color' => 'cyan',
                'accessible_scopes' => ['Multi-Branch Facilities', 'Compliance & HIPAA', 'Staff & Master Data', 'BI Reports'],
            ],
            [
                'role_key' => 'doctor',
                'role_label' => 'Doctor / Clinician',
                'email' => 'doctor@hms.local',
                'password' => 'password123',
                'name' => 'Dr. Eleanor Vance, MD',
                'tag' => 'Clinical Medicine',
                'color' => 'blue',
                'accessible_scopes' => ['Doctor Dashboard', 'EHR Clinical History', 'SOAP Consultations', 'Prescriptions & Lab Orders'],
            ],
            [
                'role_key' => 'nurse',
                'role_label' => 'Inpatient Nurse',
                'email' => 'nurse@hms.local',
                'password' => 'password123',
                'name' => 'Sister Clara Oswald, RN',
                'tag' => 'Ward & ICU Care',
                'color' => 'emerald',
                'accessible_scopes' => ['Bed Map Visuals', 'Nursing Station (Vitals/Meds)', 'Patient Intake', 'Discharge Summaries'],
            ],
            [
                'role_key' => 'pharmacist',
                'role_label' => 'Chief Pharmacist',
                'email' => 'pharmacist@hms.local',
                'password' => 'password123',
                'name' => 'Marcus Holloway, PharmD',
                'tag' => 'Pharmacy & FEFO',
                'color' => 'teal',
                'accessible_scopes' => ['Pharmacy Dispensing', 'Drug Batches & Expiry', 'Stock Reorder Alerts', 'Patient Prescriptions'],
            ],
            [
                'role_key' => 'billing_officer',
                'role_label' => 'Billing Officer',
                'email' => 'billing@hms.local',
                'password' => 'password123',
                'name' => 'Jennifer Blake',
                'tag' => 'Finance & Insurance',
                'color' => 'amber',
                'accessible_scopes' => ['Invoices & Payments', 'Insurance Claims', 'Approvals Queue', 'Revenue Analytics'],
            ],
            [
                'role_key' => 'receptionist',
                'role_label' => 'Reception / Registrar',
                'email' => 'receptionist@hms.local',
                'password' => 'password123',
                'name' => 'Sarah Connor',
                'tag' => 'Front Desk & OPD',
                'color' => 'purple',
                'accessible_scopes' => ['Patient Registration', 'Doctor Booking Calendar', 'OPD Queue Tokens & TV Display'],
            ],
        ];
    }
}
