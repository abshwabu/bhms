<?php

namespace Tests\Feature;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HrDomainTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;
    protected Branch $branch;
    protected User $user;
    protected StaffRole $doctorRole;
    protected StaffRole $nurseRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'Metro Health System',
            'code' => 'MHS',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'organization_id' => $this->organization->id,
            'name' => 'Metro General Hospital',
            'code' => 'MGH',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'organization_id' => $this->organization->id,
            'default_branch_id' => $this->branch->id,
        ]);

        Sanctum::actingAs($this->user);

        $this->doctorRole = StaffRole::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'code' => 'PHYSICIAN',
            'name' => 'Attending Physician',
            'department' => 'Internal Medicine',
            'is_medical' => true,
        ]);

        $this->nurseRole = StaffRole::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'code' => 'NURSE',
            'name' => 'Registered Nurse',
            'department' => 'Nursing',
            'is_medical' => true,
        ]);
    }

    /**
     * Test 1: Staff profile creation and automated leave quota balance initialization.
     */
    public function test_can_create_staff_profile_with_initial_leave_balances(): void
    {
        $payload = [
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'staff_role_id' => $this->doctorRole->id,
            'employee_id' => 'EMP-TEST-01',
            'first_name' => 'Julian',
            'last_name' => 'Bashir',
            'email' => 'j.bashir@hospital.org',
            'phone' => '+1555123998',
            'department' => 'Internal Medicine',
            'designation' => 'Chief Medical Officer',
            'employment_type' => 'full_time',
            'joining_date' => Carbon::now()->subMonths(6)->toDateString(),
            'hourly_rate_cents' => 12000,
            'monthly_salary_cents' => 1800000,
        ];

        $response = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson('/api/v1/hr/staff', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.employee_id', 'EMP-TEST-01')
            ->assertJsonPath('data.first_name', 'Julian');

        $staffId = $response->json('data.id');

        // Verify annual leave balance was automatically initialized
        $balances = LeaveBalance::where('staff_id', $staffId)->get();
        $this->assertNotEmpty($balances);
        $annual = $balances->firstWhere('leave_type', 'annual');
        $this->assertNotNull($annual);
        $this->assertEquals(20, $annual->allocated_days);
        $this->assertEquals(20, $annual->remaining_days);
    }

    /**
     * Test 2: Credential expiry alerts for licenses expiring soon or already expired.
     */
    public function test_credential_expiry_proactive_alerts(): void
    {
        $staff = Staff::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'staff_role_id' => $this->doctorRole->id,
            'employee_id' => 'EMP-TEST-02',
            'first_name' => 'Leonard',
            'last_name' => 'McCoy',
            'email' => 'bones@hospital.org',
            'department' => 'Internal Medicine',
            'designation' => 'Attending Physician',
            'joining_date' => Carbon::now()->subYears(2)->toDateString(),
        ]);

        // 1. Expired Credential (expired 10 days ago)
        Credential::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $staff->id,
            'credential_type' => 'board_certification',
            'title' => 'ABIM Board Certification',
            'license_number' => 'ABIM-999',
            'issuing_authority' => 'ABIM',
            'issue_date' => Carbon::now()->subYears(10)->toDateString(),
            'expiry_date' => Carbon::now()->subDays(10)->toDateString(),
            'verification_status' => 'expired',
        ]);

        // 2. Expiring Soon Credential (expires in 15 days)
        Credential::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $staff->id,
            'credential_type' => 'dea_registration',
            'title' => 'DEA Schedule II-V',
            'license_number' => 'DEA-888',
            'issuing_authority' => 'DEA',
            'issue_date' => Carbon::now()->subYears(3)->toDateString(),
            'expiry_date' => Carbon::now()->addDays(15)->toDateString(),
            'verification_status' => 'expiring_soon',
        ]);

        // 3. Active Credential (expires in 2 years)
        Credential::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $staff->id,
            'credential_type' => 'medical_license',
            'title' => 'State Medical License',
            'license_number' => 'MD-777',
            'issuing_authority' => 'State Board',
            'issue_date' => Carbon::now()->subYear()->toDateString(),
            'expiry_date' => Carbon::now()->addYears(2)->toDateString(),
            'verification_status' => 'active',
        ]);

        $response = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->getJson("/api/v1/hr/credentials/alerts?branch_id={$this->branch->id}&upcoming_days=30");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertEquals(2, $data['total_alerts_count']);
        $this->assertEquals(1, $data['expired_count']);
        $this->assertEquals(1, $data['expiring_soon_count']);

        $expiredList = $data['expired_credentials'];
        $this->assertEquals('ABIM-999', $expiredList[0]['license_number']);

        $expiringSoonList = $data['expiring_credentials'];
        $this->assertEquals('DEA-888', $expiringSoonList[0]['license_number']);
    }

    /**
     * Test 3: Roster scheduling and double-booking conflict prevention.
     */
    public function test_shift_scheduling_and_conflict_double_booking_prevention(): void
    {
        $staff = Staff::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'staff_role_id' => $this->doctorRole->id,
            'employee_id' => 'EMP-TEST-03',
            'first_name' => 'Beverly',
            'last_name' => 'Crusher',
            'email' => 'b.crusher@hospital.org',
            'department' => 'Internal Medicine',
            'designation' => 'Attending Physician',
            'joining_date' => Carbon::now()->subYears(1)->toDateString(),
        ]);

        $tomorrow = Carbon::tomorrow();
        
        // 1. Create first shift: 08:00 to 16:00
        $shift1Response = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson('/api/v1/hr/shifts', [
                'staff_id' => $staff->id,
                'shift_name' => 'Morning Shift',
                'shift_type' => 'morning',
                'department' => 'Internal Medicine',
                'shift_date' => $tomorrow->toDateString(),
                'start_time' => '08:00',
                'end_time' => '16:00',
                'notes' => 'Primary clinical roster',
            ]);

        $shift1Response->assertStatus(201)
            ->assertJsonPath('success', true);

        // 2. Pre-flight conflict check for overlapping shift (12:00 to 20:00)
        $conflictCheck = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson('/api/v1/hr/shifts/check-conflicts', [
                'staff_id' => $staff->id,
                'shift_date' => $tomorrow->toDateString(),
                'start_time' => '12:00',
                'end_time' => '20:00',
            ]);

        $conflictCheck->assertStatus(200)
            ->assertJsonPath('data.has_conflict', true)
            ->assertJsonCount(1, 'data.conflicts');

        // 3. Attempting to schedule overlapping shift without override should fail
        $overlapResponse = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson('/api/v1/hr/shifts', [
                'staff_id' => $staff->id,
                'shift_name' => 'Afternoon Overlap Shift',
                'shift_type' => 'evening',
                'department' => 'Internal Medicine',
                'shift_date' => $tomorrow->toDateString(),
                'start_time' => '12:00',
                'end_time' => '20:00',
            ]);

        $overlapResponse->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'ROSTER_CONFLICT_ERROR');
    }

    /**
     * Test 4: Leave request submission, pending quota hold, and supervisor approval balance deduction.
     */
    public function test_leave_request_submission_and_approval_workflow(): void
    {
        $staff = Staff::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'staff_role_id' => $this->nurseRole->id,
            'employee_id' => 'EMP-TEST-04',
            'first_name' => 'Christine',
            'last_name' => 'Chapel',
            'email' => 'c.chapel@hospital.org',
            'department' => 'Nursing',
            'designation' => 'Staff Nurse',
            'joining_date' => Carbon::now()->subYears(1)->toDateString(),
        ]);

        $year = Carbon::now()->year;
        LeaveBalance::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $staff->id,
            'year' => $year,
            'leave_type' => 'annual',
            'allocated_days' => 20,
            'used_days' => 0,
            'pending_days' => 0,
            'remaining_days' => 20,
        ]);

        // 1. Submit leave request for 4 days
        $startDate = Carbon::now()->addDays(10)->toDateString();
        $endDate = Carbon::now()->addDays(13)->toDateString();

        $submitResponse = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson('/api/v1/hr/leave-requests', [
                'staff_id' => $staff->id,
                'leave_type' => 'annual',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_days' => 4,
                'reason' => 'Family leave',
            ]);

        $submitResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending');

        $requestId = $submitResponse->json('data.id');

        // Check balance: pending_days=4, remaining_days=16
        $balance = LeaveBalance::where('staff_id', $staff->id)
            ->where('leave_type', 'annual')
            ->where('year', $year)
            ->first();

        $this->assertEquals(4, $balance->pending_days);
        $this->assertEquals(16, $balance->remaining_days);

        // 2. Supervisor approves leave request
        $approveResponse = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson("/api/v1/hr/leave-requests/{$requestId}/approve");

        $approveResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'approved');

        // Check balance after approval: used_days=4, pending_days=0, remaining_days=16
        $balance->refresh();
        $this->assertEquals(4, $balance->used_days);
        $this->assertEquals(0, $balance->pending_days);
        $this->assertEquals(16, $balance->remaining_days);
    }

    /**
     * Test 5: Leave rejection restores pending days to remaining quota.
     */
    public function test_leave_request_rejection_restores_quota(): void
    {
        $staff = Staff::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'staff_role_id' => $this->nurseRole->id,
            'employee_id' => 'EMP-TEST-05',
            'first_name' => 'Alyssa',
            'last_name' => 'Ogawa',
            'email' => 'a.ogawa@hospital.org',
            'department' => 'Nursing',
            'designation' => 'Staff Nurse',
            'joining_date' => Carbon::now()->subYears(1)->toDateString(),
        ]);

        $year = Carbon::now()->year;
        LeaveBalance::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $staff->id,
            'year' => $year,
            'leave_type' => 'annual',
            'allocated_days' => 20,
            'used_days' => 0,
            'pending_days' => 0,
            'remaining_days' => 20,
        ]);

        $submitResponse = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson('/api/v1/hr/leave-requests', [
                'staff_id' => $staff->id,
                'leave_type' => 'annual',
                'start_date' => Carbon::now()->addDays(5)->toDateString(),
                'end_date' => Carbon::now()->addDays(7)->toDateString(),
                'total_days' => 3,
                'reason' => 'Vacation request',
            ]);

        $requestId = $submitResponse->json('data.id');

        // Supervisor rejects
        $rejectResponse = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson("/api/v1/hr/leave-requests/{$requestId}/reject", [
                'reason' => 'Critical staffing shortage during requested week.',
            ]);

        $rejectResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'rejected');

        // Remaining days should revert to 20, pending to 0
        $balance = LeaveBalance::where('staff_id', $staff->id)
            ->where('leave_type', 'annual')
            ->where('year', $year)
            ->first();

        $this->assertEquals(0, $balance->pending_days);
        $this->assertEquals(20, $balance->remaining_days);
        $this->assertEquals(0, $balance->used_days);
    }

    /**
     * Test 6: Attendance clock-in, clock-out, and punctuality calculation.
     */
    public function test_attendance_clock_in_and_punctuality_tracking(): void
    {
        $staff = Staff::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'staff_role_id' => $this->doctorRole->id,
            'employee_id' => 'EMP-TEST-06',
            'first_name' => 'Toby',
            'last_name' => 'Russell',
            'email' => 't.russell@hospital.org',
            'department' => 'Internal Medicine',
            'designation' => 'Resident Physician',
            'joining_date' => Carbon::now()->subMonths(3)->toDateString(),
        ]);

        $today = Carbon::today();

        $shift = Shift::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $staff->id,
            'shift_name' => 'Morning Ward Shift',
            'shift_type' => 'morning',
            'department' => 'Internal Medicine',
            'shift_date' => $today->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'start_datetime' => $today->copy()->setTime(8, 0, 0),
            'end_datetime' => $today->copy()->setTime(16, 0, 0),
            'is_published' => true,
        ]);

        // Clock in 20 minutes late (08:20)
        $clockInTime = $today->copy()->setTime(8, 20, 0)->toIso8601String();

        $checkInResponse = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson('/api/v1/hr/attendance/check-in', [
                'staff_id' => $staff->id,
                'shift_id' => $shift->id,
                'check_in_time' => $clockInTime,
            ]);

        $checkInResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'late')
            ->assertJsonPath('data.is_punctual', false)
            ->assertJsonPath('data.minutes_late', 20);

        // Clock out at 16:30 (total 490 minutes)
        $clockOutTime = $today->copy()->setTime(16, 30, 0)->toIso8601String();

        $checkOutResponse = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson('/api/v1/hr/attendance/check-out', [
                'staff_id' => $staff->id,
                'check_out_time' => $clockOutTime,
            ]);

        $checkOutResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_minutes_worked', 490);
    }
}
