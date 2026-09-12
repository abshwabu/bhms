<?php

namespace Database\Seeders;

use App\Domain\HR\Models\Attendance;
use App\Domain\HR\Models\Credential;
use App\Domain\HR\Models\LeaveBalance;
use App\Domain\HR\Models\LeaveRequest;
use App\Domain\HR\Models\Shift;
use App\Domain\HR\Models\Staff;
use App\Domain\HR\Models\StaffRole;
use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class HrModuleSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::first();
        $branch = Branch::first();
        $adminUser = User::first();

        if (!$organization || !$branch) {
            return;
        }

        // 1. Seed Staff Roles
        $rolesData = [
            [
                'code' => 'CMO',
                'name' => 'Chief Medical Officer',
                'department' => 'Executive & Clinical Leadership',
                'description' => 'Oversees all clinical operations, physician credentials, and medical governance.',
                'is_medical' => true,
            ],
            [
                'code' => 'CONS-PHYS',
                'name' => 'Senior Consultant Physician',
                'department' => 'Internal Medicine',
                'description' => 'Specialist medical practitioner managing adult patient diagnosis and inpatient care.',
                'is_medical' => true,
            ],
            [
                'code' => 'CHG-NURSE',
                'name' => 'Charge Nurse',
                'department' => 'Nursing & Inpatient Care',
                'description' => 'Shift lead supervising ward nurses, triage operations, and bedside care.',
                'is_medical' => true,
            ],
            [
                'code' => 'SR-RAD-TECH',
                'name' => 'Senior Radiographer',
                'department' => 'Radiology & Imaging',
                'description' => 'Licensed imaging professional performing MRI, CT, and X-ray acquisitions.',
                'is_medical' => true,
            ],
            [
                'code' => 'CLIN-PHARM',
                'name' => 'Clinical Pharmacist',
                'department' => 'Pharmacy & Pharmacology',
                'description' => 'Licensed pharmacist reviewing medication safety, dosing, and dispensing.',
                'is_medical' => true,
            ],
            [
                'code' => 'HR-MGR',
                'name' => 'HR Operations Manager',
                'department' => 'Human Resources',
                'description' => 'Supervises staff onboarding, compliance credentialing, and duty rosters.',
                'is_medical' => false,
            ],
        ];

        $roles = [];
        foreach ($rolesData as $r) {
            $roles[$r['code']] = StaffRole::updateOrCreate(
                ['code' => $r['code'], 'organization_id' => $organization->id],
                array_merge($r, [
                    'organization_id' => $organization->id,
                    'branch_id' => $branch->id,
                ])
            );
        }

        // 2. Seed Staff Members
        $staffMembers = [
            [
                'employee_id' => 'EMP-00101',
                'first_name' => 'Evelyn',
                'last_name' => 'Vance',
                'email' => 'dr.vance@hospital.org',
                'phone' => '+1 (555) 301-4401',
                'department' => 'Internal Medicine',
                'staff_role_id' => $roles['CMO']->id,
                'designation' => 'Chief Medical Officer & Consultant',
                'employment_type' => 'full_time',
                'joining_date' => Carbon::now()->subYears(4)->toDateString(),
                'status' => 'active',
                'hourly_rate_cents' => 12500, // $125.00/hr
                'monthly_salary_cents' => 2000000, // $20,000/mo
                'emergency_contact' => ['name' => 'Thomas Vance (Spouse)', 'phone' => '+1 (555) 902-8811'],
            ],
            [
                'employee_id' => 'EMP-00102',
                'first_name' => 'Marcus',
                'last_name' => 'Cole',
                'email' => 'dr.cole@hospital.org',
                'phone' => '+1 (555) 301-4402',
                'department' => 'Internal Medicine',
                'staff_role_id' => $roles['CONS-PHYS']->id,
                'designation' => 'Senior Consultant Physician',
                'employment_type' => 'full_time',
                'joining_date' => Carbon::now()->subYears(2)->toDateString(),
                'status' => 'active',
                'hourly_rate_cents' => 9500, // $95.00/hr
                'monthly_salary_cents' => 1500000, // $15,000/mo
                'emergency_contact' => ['name' => 'Andrea Cole (Spouse)', 'phone' => '+1 (555) 902-3322'],
            ],
            [
                'employee_id' => 'EMP-00103',
                'first_name' => 'Sarah',
                'last_name' => 'Jenkins',
                'email' => 's.jenkins@hospital.org',
                'phone' => '+1 (555) 301-4403',
                'department' => 'Nursing',
                'staff_role_id' => $roles['CHG-NURSE']->id,
                'designation' => 'ICU Charge Nurse',
                'employment_type' => 'full_time',
                'joining_date' => Carbon::now()->subMonths(18)->toDateString(),
                'status' => 'active',
                'hourly_rate_cents' => 4500, // $45.00/hr
                'monthly_salary_cents' => 720000, // $7,200/mo
                'emergency_contact' => ['name' => 'Michael Jenkins (Father)', 'phone' => '+1 (555) 902-5544'],
            ],
            [
                'employee_id' => 'EMP-00104',
                'first_name' => 'David',
                'last_name' => 'Kim',
                'email' => 'd.kim@hospital.org',
                'phone' => '+1 (555) 301-4404',
                'department' => 'Radiology',
                'staff_role_id' => $roles['SR-RAD-TECH']->id,
                'designation' => 'Lead MRI & CT Technologist',
                'employment_type' => 'full_time',
                'joining_date' => Carbon::now()->subMonths(14)->toDateString(),
                'status' => 'active',
                'hourly_rate_cents' => 4200, // $42.00/hr
                'monthly_salary_cents' => 670000, // $6,700/mo
                'emergency_contact' => ['name' => 'Hanna Kim (Sister)', 'phone' => '+1 (555) 902-6677'],
            ],
            [
                'employee_id' => 'EMP-00105',
                'first_name' => 'Elena',
                'last_name' => 'Rostova',
                'email' => 'elena.rostova@hospital.org',
                'phone' => '+1 (555) 301-4405',
                'department' => 'Human Resources',
                'staff_role_id' => $roles['HR-MGR']->id,
                'designation' => 'HR Operations Lead',
                'employment_type' => 'full_time',
                'joining_date' => Carbon::now()->subYears(1)->toDateString(),
                'status' => 'active',
                'hourly_rate_cents' => 4000,
                'monthly_salary_cents' => 640000,
                'emergency_contact' => ['name' => 'Petr Rostov (Brother)', 'phone' => '+1 (555) 902-9988'],
            ],
        ];

        $createdStaff = [];
        foreach ($staffMembers as $s) {
            $staff = Staff::updateOrCreate(
                ['employee_id' => $s['employee_id']],
                array_merge($s, [
                    'organization_id' => $organization->id,
                    'branch_id' => $branch->id,
                    'user_id' => $adminUser?->id,
                ])
            );
            $createdStaff[$s['employee_id']] = $staff;

            // Seed standard leave balances for current calendar year
            $year = Carbon::now()->year;
            $leaveTypes = [
                ['leave_type' => 'annual', 'allocated' => 20],
                ['leave_type' => 'sick', 'allocated' => 12],
                ['leave_type' => 'cme', 'allocated' => 5],
            ];

            foreach ($leaveTypes as $lt) {
                LeaveBalance::firstOrCreate(
                    [
                        'staff_id' => $staff->id,
                        'year' => $year,
                        'leave_type' => $lt['leave_type'],
                    ],
                    [
                        'organization_id' => $organization->id,
                        'branch_id' => $branch->id,
                        'allocated_days' => $lt['allocated'],
                        'used_days' => 0,
                        'pending_days' => 0,
                        'remaining_days' => $lt['allocated'],
                    ]
                );
            }
        }

        // 3. Seed Credentials (Active, Expiring Soon, and Expired for alert demonstrations)
        $vance = $createdStaff['EMP-00101'];
        $cole = $createdStaff['EMP-00102'];
        $jenkins = $createdStaff['EMP-00103'];
        $kim = $createdStaff['EMP-00104'];

        // Vance: Active Medical License
        Credential::updateOrCreate(
            ['staff_id' => $vance->id, 'license_number' => 'MD-LIC-998234'],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'credential_type' => 'medical_license',
                'title' => 'State Board of Medicine - Physician & Surgeon License',
                'issuing_authority' => 'State Medical Licensing Board',
                'issue_date' => Carbon::now()->subYears(3)->toDateString(),
                'expiry_date' => Carbon::now()->addYears(2)->toDateString(),
                'verification_status' => 'active',
                'verified_at' => Carbon::now()->subMonths(6),
                'verified_by' => $adminUser?->id,
            ]
        );

        // Vance: DEA Registration EXPIRING SOON (in 12 days -> triggers warning alert!)
        Credential::updateOrCreate(
            ['staff_id' => $vance->id, 'license_number' => 'DEA-BV-772910'],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'credential_type' => 'dea_registration',
                'title' => 'Drug Enforcement Administration (DEA) Schedule II-V',
                'issuing_authority' => 'US Drug Enforcement Administration',
                'issue_date' => Carbon::now()->subYears(3)->toDateString(),
                'expiry_date' => Carbon::now()->addDays(12)->toDateString(),
                'verification_status' => 'expiring_soon',
                'verified_at' => Carbon::now()->subMonths(10),
                'verified_by' => $adminUser?->id,
                'notes' => 'Renewal application initiated with state pharmacy board.',
            ]
        );

        // Cole: Board Certification EXPIRED (expired 15 days ago -> triggers critical alert!)
        Credential::updateOrCreate(
            ['staff_id' => $cole->id, 'license_number' => 'ABIM-CERT-44190'],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'credential_type' => 'board_certification',
                'title' => 'American Board of Internal Medicine (ABIM) Certification',
                'issuing_authority' => 'American Board of Internal Medicine',
                'issue_date' => Carbon::now()->subYears(10)->toDateString(),
                'expiry_date' => Carbon::now()->subDays(15)->toDateString(),
                'verification_status' => 'expired',
                'notes' => 'CRITICAL: License expired! Staff notified to upload renewal documentation immediately.',
            ]
        );

        // Jenkins: Nursing License (Active)
        Credential::updateOrCreate(
            ['staff_id' => $jenkins->id, 'license_number' => 'RN-LIC-671290'],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'credential_type' => 'nursing_council',
                'title' => 'Registered Professional Nurse (RN) License',
                'issuing_authority' => 'State Board of Nursing',
                'issue_date' => Carbon::now()->subYears(2)->toDateString(),
                'expiry_date' => Carbon::now()->addMonths(14)->toDateString(),
                'verification_status' => 'active',
                'verified_at' => Carbon::now()->subMonths(4),
                'verified_by' => $adminUser?->id,
            ]
        );

        // Kim: ARRT Radiography (Active)
        Credential::updateOrCreate(
            ['staff_id' => $kim->id, 'license_number' => 'ARRT-RT-389102'],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'credential_type' => 'certification',
                'title' => 'ARRT Registered Technologist (Radiography & CT)',
                'issuing_authority' => 'American Registry of Radiologic Technologists',
                'issue_date' => Carbon::now()->subYears(3)->toDateString(),
                'expiry_date' => Carbon::now()->addMonths(9)->toDateString(),
                'verification_status' => 'active',
            ]
        );

        // 4. Seed Duty Roster Shifts
        $today = Carbon::today();
        
        // Dr. Vance: Morning shift today (Published)
        $shiftVanceToday = Shift::updateOrCreate(
            [
                'staff_id' => $vance->id,
                'shift_date' => $today->toDateString(),
                'shift_type' => 'morning',
            ],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'shift_name' => 'Internal Medicine Morning OPD',
                'department' => 'Internal Medicine',
                'start_time' => '08:00:00',
                'end_time' => '16:00:00',
                'start_datetime' => $today->copy()->setTime(8, 0, 0),
                'end_datetime' => $today->copy()->setTime(16, 0, 0),
                'status' => 'scheduled',
                'is_published' => true,
                'created_by' => $adminUser?->id,
                'notes' => 'Attending physician OPD consults.',
            ]
        );

        // Dr. Cole: Night shift today (Published)
        Shift::updateOrCreate(
            [
                'staff_id' => $cole->id,
                'shift_date' => $today->toDateString(),
                'shift_type' => 'night',
            ],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'shift_name' => 'Inpatient Night Duty',
                'department' => 'Internal Medicine',
                'start_time' => '20:00:00',
                'end_time' => '08:00:00',
                'start_datetime' => $today->copy()->setTime(20, 0, 0),
                'end_datetime' => $today->copy()->addDay()->setTime(8, 0, 0),
                'status' => 'scheduled',
                'is_published' => true,
                'created_by' => $adminUser?->id,
                'notes' => 'Inpatient ward rounds & acute admissions coverage.',
            ]
        );

        // Nurse Jenkins: Morning shift today (Published)
        $shiftJenkinsToday = Shift::updateOrCreate(
            [
                'staff_id' => $jenkins->id,
                'shift_date' => $today->toDateString(),
                'shift_type' => 'morning',
            ],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'shift_name' => 'ICU Morning Nursing Lead',
                'department' => 'Nursing',
                'start_time' => '07:30:00',
                'end_time' => '15:30:00',
                'start_datetime' => $today->copy()->setTime(7, 30, 0),
                'end_datetime' => $today->copy()->setTime(15, 30, 0),
                'status' => 'scheduled',
                'is_published' => true,
                'created_by' => $adminUser?->id,
                'notes' => 'ICU Ward 2 Charge Nurse.',
            ]
        );

        // David Kim: Afternoon shift today (Published)
        Shift::updateOrCreate(
            [
                'staff_id' => $kim->id,
                'shift_date' => $today->toDateString(),
                'shift_type' => 'evening',
            ],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'shift_name' => 'Radiology Evening Coverage',
                'department' => 'Radiology',
                'start_time' => '14:00:00',
                'end_time' => '22:00:00',
                'start_datetime' => $today->copy()->setTime(14, 0, 0),
                'end_datetime' => $today->copy()->setTime(22, 0, 0),
                'status' => 'scheduled',
                'is_published' => true,
                'created_by' => $adminUser?->id,
                'notes' => 'MRI and Trauma CT coverage.',
            ]
        );

        // 5. Seed Attendance Records
        // Dr. Vance checked in today at 07:55 AM (on time)
        Attendance::updateOrCreate(
            [
                'staff_id' => $vance->id,
                'date' => $today->toDateString(),
            ],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'shift_id' => $shiftVanceToday->id,
                'check_in_time' => $today->copy()->setTime(7, 55, 0),
                'total_minutes_worked' => 485,
                'is_punctual' => true,
                'minutes_late' => 0,
                'status' => 'present',
                'notes' => 'Arrived 5 mins prior to clinic commencement.',
            ]
        );

        // Nurse Jenkins checked in today at 07:42 AM (12 minutes late for 07:30 shift)
        Attendance::updateOrCreate(
            [
                'staff_id' => $jenkins->id,
                'date' => $today->toDateString(),
            ],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'shift_id' => $shiftJenkinsToday->id,
                'check_in_time' => $today->copy()->setTime(7, 42, 0),
                'total_minutes_worked' => 468,
                'is_punctual' => false,
                'minutes_late' => 12,
                'status' => 'late',
                'notes' => 'Delayed by transit traffic.',
            ]
        );

        // 6. Seed Leave Requests
        // Dr. Cole: Pending Leave Request for next week (3 days)
        $leaveBal = LeaveBalance::where('staff_id', $cole->id)
            ->where('leave_type', 'annual')
            ->where('year', Carbon::now()->year)
            ->first();

        if ($leaveBal && $leaveBal->pending_days == 0) {
            $startDate = Carbon::now()->addDays(7)->toDateString();
            $endDate = Carbon::now()->addDays(9)->toDateString();
            
            LeaveRequest::create([
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'staff_id' => $cole->id,
                'leave_type' => 'annual',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_days' => 3,
                'status' => 'pending',
                'reason' => 'Annual family vacation and rest.',
            ]);

            $leaveBal->update([
                'pending_days' => 3,
                'remaining_days' => $leaveBal->allocated_days - 3,
            ]);
        }
    }
}
