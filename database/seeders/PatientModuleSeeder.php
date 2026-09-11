<?php

namespace Database\Seeders;

use App\Domain\Patient\Actions\RegisterPatientAction;
use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PatientModuleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Organization
        $organization = Organization::firstOrCreate(
            ['code' => 'MHS'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Metro Health System',
                'tax_number' => 'TAX-MHS-99882',
                'settings' => ['currency' => 'USD', 'timezone' => 'UTC'],
                'is_active' => true,
            ]
        );

        // 2. Create Branches
        $mainBranch = Branch::firstOrCreate(
            ['organization_id' => $organization->id, 'code' => 'MAIN'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Metro General Hospital (Main Campus)',
                'phone' => '+1-555-0100',
                'email' => 'main@metrohealth.org',
                'address' => [
                    'street' => '100 Medical Center Blvd',
                    'city' => 'Metropolis',
                    'state' => 'NY',
                    'postal_code' => '10001',
                    'country' => 'USA',
                ],
                'settings' => ['emergency_department' => true, 'beds' => 500],
                'is_active' => true,
            ]
        );

        $northBranch = Branch::firstOrCreate(
            ['organization_id' => $organization->id, 'code' => 'NORTH'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Metro North Outpatient Clinic',
                'phone' => '+1-555-0200',
                'email' => 'north@metrohealth.org',
                'address' => [
                    'street' => '450 Northway Ave',
                    'city' => 'Metropolis',
                    'state' => 'NY',
                    'postal_code' => '10025',
                    'country' => 'USA',
                ],
                'settings' => ['emergency_department' => false, 'beds' => 20],
                'is_active' => true,
            ]
        );

        // Bind branch context for Spatie permissions
        setPermissionsTeamId($mainBranch->id);

        // 3. Create Permissions
        $permissions = [
            'patient.records.view',
            'patient.records.create',
            'patient.records.update',
            'patient.records.delete',
            'clinical.history.view',
            'clinical.history.modify',
            'billing.insurance.manage',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        // 4. Create Roles
        $roles = [
            'super_admin' => $permissions,
            'hospital_admin' => $permissions,
            'doctor' => [
                'patient.records.view',
                'patient.records.create',
                'patient.records.update',
                'clinical.history.view',
                'clinical.history.modify',
            ],
            'nurse' => [
                'patient.records.view',
                'patient.records.update',
                'clinical.history.view',
                'clinical.history.modify',
            ],
            'pharmacist' => [
                'patient.records.view',
                'clinical.history.view',
            ],
            'lab_technician' => [
                'patient.records.view',
            ],
            'billing_officer' => [
                'patient.records.view',
                'billing.insurance.manage',
            ],
            'receptionist' => [
                'patient.records.view',
                'patient.records.create',
                'patient.records.update',
            ],
            'patient' => [],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
                'branch_id' => $mainBranch->id,
            ]);

            if (!empty($rolePermissions)) {
                $role->syncPermissions($rolePermissions);
            }
        }

        // 5. Create Staff Users
        $doctorUser = User::firstOrCreate(
            ['email' => 'doctor@hms.local'],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'default_branch_id' => $mainBranch->id,
                'employee_id' => 'EMP-DOC-001',
                'name' => 'Dr. Eleanor Vance, MD',
                'phone' => '+1-555-1111',
                'password' => Hash::make('password123'),
                'is_active' => true,
                'is_patient' => false,
            ]
        );
        $doctorUser->assignRole('doctor');
        $doctorUser->branches()->syncWithoutDetaching([$mainBranch->id => ['is_default' => true]]);

        $receptionistUser = User::firstOrCreate(
            ['email' => 'receptionist@hms.local'],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'default_branch_id' => $mainBranch->id,
                'employee_id' => 'EMP-REC-001',
                'name' => 'Sarah Connor',
                'phone' => '+1-555-2222',
                'password' => Hash::make('password123'),
                'is_active' => true,
                'is_patient' => false,
            ]
        );
        $receptionistUser->assignRole('receptionist');
        $receptionistUser->branches()->syncWithoutDetaching([$mainBranch->id => ['is_default' => true]]);

        // 6. Register Seed Patients via RegisterPatientAction
        $registerAction = app(RegisterPatientAction::class);

        // Walk-in Patient (John Doe)
        $john = $registerAction->execute([
            'registration_type' => 'walk_in',
            'first_name' => 'John',
            'middle_name' => 'William',
            'last_name' => 'Doe',
            'date_of_birth' => '1985-06-15',
            'gender' => 'male',
            'blood_group' => 'O+',
            'national_id' => 'NAT-850615-101',
            'phone' => '+1-555-9001',
            'email' => 'john.doe@example.com',
            'marital_status' => 'married',
            'occupation' => 'Software Architect',
            'address' => [
                'street' => '742 Evergreen Terrace',
                'city' => 'Metropolis',
                'state' => 'NY',
                'postal_code' => '10012',
            ],
            'emergency_contact' => [
                'name' => 'Jane Doe',
                'relationship' => 'Spouse',
                'phone' => '+1-555-9002',
            ],
            'initial_history' => [
                [
                    'category' => 'chronic_condition',
                    'condition_or_procedure' => 'Essential Hypertension',
                    'icd10_code' => 'I10',
                    'diagnosed_date' => '2020-03-10',
                    'status' => 'managed',
                    'severity' => 'mild',
                ],
                [
                    'category' => 'surgical_history',
                    'condition_or_procedure' => 'Laparoscopic Appendectomy',
                    'diagnosed_date' => '2015-08-22',
                    'status' => 'resolved',
                ],
            ],
            'initial_allergies' => [
                [
                    'allergen' => 'Penicillin',
                    'allergen_type' => 'drug',
                    'reaction' => 'Severe Urticaria & Angioedema',
                    'severity' => 'severe',
                ],
                [
                    'allergen' => 'Peanuts',
                    'allergen_type' => 'food',
                    'reaction' => 'Anaphylaxis',
                    'severity' => 'life_threatening',
                ],
            ],
            'initial_insurance' => [
                'provider_name' => 'Blue Cross Blue Shield',
                'policy_number' => 'BCBS-778899',
                'group_number' => 'GRP-5544',
                'coverage_type' => 'primary',
                'coverage_percentage' => 85.00,
                'copay_amount_cents' => 2500, // $25.00
                'valid_from' => '2026-01-01',
                'valid_until' => '2026-12-31',
            ],
        ], $mainBranch, $receptionistUser->id);

        // Dependent Child of John Doe (Tommy Doe)
        $tommy = $registerAction->execute([
            'registration_type' => 'walk_in',
            'first_name' => 'Tommy',
            'last_name' => 'Doe',
            'date_of_birth' => '2018-09-20',
            'gender' => 'male',
            'blood_group' => 'O+',
            'national_id' => 'NAT-180920-808',
            'address' => [
                'street' => '742 Evergreen Terrace',
                'city' => 'Metropolis',
                'state' => 'NY',
                'postal_code' => '10012',
            ],
            'initial_relationship' => [
                'related_patient_id' => $john->id,
                'relationship_type' => 'child',
                'is_guardian' => false,
                'is_emergency_contact' => true,
            ],
        ], $mainBranch, $receptionistUser->id);

        // Referral Patient (Amina Yusuf)
        $registerAction->execute([
            'registration_type' => 'referral',
            'referral_source' => 'City Care Health Clinic / Dr. Marcus Welby',
            'first_name' => 'Amina',
            'last_name' => 'Yusuf',
            'date_of_birth' => '1992-11-04',
            'gender' => 'female',
            'blood_group' => 'A+',
            'national_id' => 'NAT-921104-450',
            'phone' => '+1-555-8833',
            'email' => 'amina.yusuf@example.com',
            'marital_status' => 'single',
            'occupation' => 'Biologist',
            'initial_allergies' => [
                [
                    'allergen' => 'Latex',
                    'allergen_type' => 'environmental',
                    'reaction' => 'Contact Dermatitis',
                    'severity' => 'moderate',
                ],
            ],
            'initial_insurance' => [
                'provider_name' => 'Aetna Healthcare',
                'policy_number' => 'AET-443322',
                'coverage_type' => 'primary',
                'coverage_percentage' => 90.00,
                'valid_from' => '2026-01-01',
                'valid_until' => '2027-01-01',
            ],
        ], $mainBranch, $receptionistUser->id);

        // Emergency Intake Patient (Unknown Trauma Intake)
        $registerAction->execute([
            'registration_type' => 'emergency',
            'triage_level' => 'critical',
            'first_name' => 'Trauma Male #1',
            'last_name' => 'Unknown',
            'is_dob_estimated' => true,
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'blood_group' => 'B-',
            'notes' => 'Brought in via EMS unconscious following motor vehicle collision. Red triage level.',
        ], $mainBranch, $doctorUser->id);
    }
}
