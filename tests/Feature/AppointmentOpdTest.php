<?php

namespace Tests\Feature;

use App\Domain\OPD\Models\Appointment;
use App\Domain\OPD\Models\ConsultationNote;
use App\Domain\OPD\Models\Department;
use App\Domain\OPD\Models\DoctorSchedule;
use App\Domain\OPD\Models\QueueToken;
use App\Domain\OPD\Models\Referral;
use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AppointmentOpdTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;
    protected Branch $branch;
    protected User $doctor1;
    protected User $doctor2;
    protected User $receptionist;
    protected Department $generalDept;
    protected Department $pediatricsDept;
    protected Patient $patient1;
    protected Patient $patient2;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Organization and Branch
        $this->org = Organization::create([
            'id' => (string) Str::uuid(),
            'name' => 'Apex Health Group',
            'code' => 'AHG-' . Str::random(4),
            'tax_number' => 'TAX-9876',
        ]);

        $this->branch = Branch::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'name' => 'Apex Central Hospital',
            'code' => 'CENTRAL',
        ]);

        // 2. Setup Departments
        $this->generalDept = Department::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'name' => 'General OPD',
            'code' => 'GEN',
            'is_active' => true,
        ]);

        $this->pediatricsDept = Department::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'name' => 'Pediatrics',
            'code' => 'PED',
            'is_active' => true,
        ]);

        // 3. Setup Users
        $this->doctor1 = User::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Dr. Stephen Strange',
            'email' => 'strange_' . Str::random(5) . '@apex.health',
            'password' => bcrypt('password123'),
        ]);

        $this->doctor2 = User::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Dr. Beverly Crusher',
            'email' => 'crusher_' . Str::random(5) . '@apex.health',
            'password' => bcrypt('password123'),
        ]);

        $this->receptionist = User::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Clara Oswald',
            'email' => 'clara_' . Str::random(5) . '@apex.health',
            'password' => bcrypt('password123'),
        ]);

        // 4. Setup Patients
        $this->patient1 = Patient::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'mrn' => 'MRN-2026-CENTRAL-0001',
            'registration_type' => 'walk_in',
            'first_name' => 'Peter',
            'last_name' => 'Parker',
            'date_of_birth' => '1998-08-10',
            'gender' => 'male',
            'phone' => '+12025550101',
        ]);

        $this->patient2 = Patient::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'mrn' => 'MRN-2026-CENTRAL-0002',
            'registration_type' => 'walk_in',
            'first_name' => 'Mary',
            'last_name' => 'Watson',
            'date_of_birth' => '1999-04-12',
            'gender' => 'female',
            'phone' => '+12025550102',
        ]);
    }

    /**
     * Helper to make authorized request with X-Branch-ID.
     */
    protected function opdRequest(User $user)
    {
        return $this->actingAs($user, 'sanctum')
            ->withHeader('X-Branch-ID', $this->branch->id);
    }

    /**
     * Test 1: Doctor schedule creation and availability calendar calculation.
     */
    public function test_doctor_schedule_and_slot_availability_generation(): void
    {
        // Doctor 1 is available on Mondays (day_of_week = 1) from 09:00 to 11:00 with 30-min slots
        $this->opdRequest($this->doctor1)->postJson('/api/v1/opd/schedules', [
            'doctor_id' => $this->doctor1->id,
            'department_id' => $this->generalDept->id,
            'schedule_type' => 'recurring',
            'day_of_week' => 1, // Monday
            'start_time' => '09:00',
            'end_time' => '11:00',
            'slot_duration_minutes' => 30,
            'max_patients' => 4,
        ])->assertStatus(201);

        // Pick next Monday
        $nextMonday = Carbon::now()->next(Carbon::MONDAY)->toDateString();

        $response = $this->opdRequest($this->doctor1)->getJson("/api/v1/opd/schedules/availability?doctor_id={$this->doctor1->id}&date={$nextMonday}");

        $response->assertStatus(200)
            ->assertJsonPath('data.date', $nextMonday)
            ->assertJsonCount(4, 'data.slots'); // 09:00, 09:30, 10:00, 10:30

        $slots = $response->json('data.slots');
        $this->assertEquals('09:00', $slots[0]['start_time']);
        $this->assertEquals('09:30', $slots[0]['end_time']);
        $this->assertFalse($slots[0]['is_booked']);
    }

    /**
     * Test 2: Conflict detection prevents double-booking of a doctor's slot.
     */
    public function test_conflict_detection_prevents_double_booking(): void
    {
        $testDate = Carbon::now()->addDays(2)->toDateString();

        // Doctor books Patient 1 for 10:00 - 10:30
        $bookingPayload = [
            'patient_id' => $this->patient1->id,
            'doctor_id' => $this->doctor1->id,
            'department_id' => $this->generalDept->id,
            'appointment_date' => $testDate,
            'start_time' => '10:00',
            'end_time' => '10:30',
            'appointment_type' => 'in_person',
            'reason_for_visit' => 'Persistent dry cough',
        ];

        $firstBooking = $this->opdRequest($this->receptionist)
            ->postJson('/api/v1/opd/appointments', $bookingPayload);

        $firstBooking->assertStatus(201)
            ->assertJsonPath('data.status', 'scheduled')
            ->assertJsonPath('data.doctor_id', $this->doctor1->id);

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $this->patient1->id,
            'doctor_id' => $this->doctor1->id,
            'appointment_date' => $testDate . ' 00:00:00',
            'start_time' => '10:00:00',
        ]);

        // Attempt second booking for Patient 2 with the SAME doctor at an OVERLAPPING slot (10:15 - 10:45)
        $conflictingPayload = [
            'patient_id' => $this->patient2->id,
            'doctor_id' => $this->doctor1->id,
            'department_id' => $this->generalDept->id,
            'appointment_date' => $testDate,
            'start_time' => '10:15',
            'end_time' => '10:45',
            'appointment_type' => 'in_person',
            'reason_for_visit' => 'Routine checkup',
        ];

        $conflictResponse = $this->opdRequest($this->receptionist)
            ->postJson('/api/v1/opd/appointments', $conflictingPayload);

        $conflictResponse->assertStatus(422)
            ->assertJsonPath('code', 'SLOT_CONFLICT');

        // Verify Patient 2's conflicting appointment was NOT saved in database
        $this->assertDatabaseMissing('appointments', [
            'patient_id' => $this->patient2->id,
            'appointment_date' => $testDate . ' 00:00:00',
        ]);

        // Booking the same time slot with a DIFFERENT doctor should succeed
        $differentDoctorPayload = [
            'patient_id' => $this->patient2->id,
            'doctor_id' => $this->doctor2->id,
            'department_id' => $this->generalDept->id,
            'appointment_date' => $testDate,
            'start_time' => '10:00',
            'end_time' => '10:30',
            'appointment_type' => 'in_person',
            'reason_for_visit' => 'Headache',
        ];

        $this->opdRequest($this->receptionist)
            ->postJson('/api/v1/opd/appointments', $differentDoctorPayload)
            ->assertStatus(201);
    }

    /**
     * Test 3: Rescheduling and Cancellation of appointments.
     */
    public function test_appointment_rescheduling_and_cancellation(): void
    {
        $date1 = Carbon::now()->addDays(3)->toDateString();
        $date2 = Carbon::now()->addDays(4)->toDateString();

        $appointment = Appointment::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'appointment_number' => 'APT-TEST-001',
            'patient_id' => $this->patient1->id,
            'doctor_id' => $this->doctor1->id,
            'department_id' => $this->generalDept->id,
            'appointment_date' => $date1,
            'start_time' => '14:00',
            'end_time' => '14:30',
            'appointment_type' => 'in_person',
            'status' => 'scheduled',
        ]);

        // Reschedule to date2 at 15:00
        $rescheduleResponse = $this->opdRequest($this->receptionist)
            ->postJson("/api/v1/opd/appointments/{$appointment->id}/reschedule", [
                'appointment_date' => $date2,
                'start_time' => '15:00',
                'end_time' => '15:30',
            ]);

        $rescheduleResponse->assertStatus(200)
            ->assertJsonPath('data.appointment_date', $date2)
            ->assertJsonPath('data.start_time', '15:00');

        // Cancel appointment
        $cancelResponse = $this->opdRequest($this->receptionist)
            ->postJson("/api/v1/opd/appointments/{$appointment->id}/cancel", [
                'cancellation_reason' => 'Patient requested due to travel conflict',
            ]);

        $cancelResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Patient requested due to travel conflict',
        ]);
    }

    /**
     * Test 4: Follow-up appointment scheduling linked to prior visit.
     */
    public function test_follow_up_appointment_links_to_prior_visit(): void
    {
        $initialDate = Carbon::now()->subDays(5)->toDateString();
        $followUpDate = Carbon::now()->addDays(7)->toDateString();

        $initialAppointment = Appointment::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'appointment_number' => 'APT-INITIAL-001',
            'patient_id' => $this->patient1->id,
            'doctor_id' => $this->doctor1->id,
            'department_id' => $this->generalDept->id,
            'appointment_date' => $initialDate,
            'start_time' => '11:00',
            'end_time' => '11:30',
            'appointment_type' => 'in_person',
            'status' => 'completed',
        ]);

        $followUpPayload = [
            'patient_id' => $this->patient1->id,
            'doctor_id' => $this->doctor1->id,
            'department_id' => $this->generalDept->id,
            'appointment_date' => $followUpDate,
            'start_time' => '11:00',
            'end_time' => '11:30',
            'appointment_type' => 'follow_up',
            'parent_appointment_id' => $initialAppointment->id,
            'reason_for_visit' => 'Follow up on lab blood tests',
        ];

        $response = $this->opdRequest($this->doctor1)
            ->postJson('/api/v1/opd/appointments', $followUpPayload);

        $response->assertStatus(201)
            ->assertJsonPath('data.appointment_type', 'follow_up')
            ->assertJsonPath('data.parent_appointment_id', $initialAppointment->id);

        $followUpId = $response->json('data.id');
        $followUpModel = Appointment::with('parentAppointment')->findOrFail($followUpId);
        $this->assertEquals($initialAppointment->id, $followUpModel->parentAppointment->id);
    }

    /**
     * Test 5: Queue tokens reset per day per department.
     */
    public function test_queue_tokens_reset_daily_per_department(): void
    {
        $day1 = Carbon::parse('2026-09-15');
        $day2 = Carbon::parse('2026-09-16');

        // Day 1, General Dept: Patient 1 gets GEN-001
        $token1 = $this->opdRequest($this->receptionist)->postJson('/api/v1/opd/queue/tokens', [
            'department_id' => $this->generalDept->id,
            'patient_id' => $this->patient1->id,
            'priority' => 'normal',
        ]);
        $token1->assertStatus(201)
            ->assertJsonPath('data.token_code', 'GEN-001')
            ->assertJsonPath('data.token_number', 1);

        // Day 1, General Dept: Patient 2 gets GEN-002
        $token2 = $this->opdRequest($this->receptionist)->postJson('/api/v1/opd/queue/tokens', [
            'department_id' => $this->generalDept->id,
            'patient_id' => $this->patient2->id,
            'priority' => 'normal',
        ]);
        $token2->assertStatus(201)
            ->assertJsonPath('data.token_code', 'GEN-002')
            ->assertJsonPath('data.token_number', 2);

        // Day 1, Pediatrics Dept: Different department starts at PED-001
        $tokenPed = $this->opdRequest($this->receptionist)->postJson('/api/v1/opd/queue/tokens', [
            'department_id' => $this->pediatricsDept->id,
            'patient_id' => $this->patient1->id,
            'priority' => 'urgent',
        ]);
        $tokenPed->assertStatus(201)
            ->assertJsonPath('data.token_code', 'PED-001')
            ->assertJsonPath('data.token_number', 1);

        // Manually simulate Next Day for General Dept:
        // Set existing tokens' token_date to day1
        QueueToken::where('department_id', $this->generalDept->id)
            ->update(['token_date' => $day1->toDateString()]);

        // When a new token is requested for today (day2), it must RESET back to 1 (GEN-001)
        $tokenNextDay = $this->opdRequest($this->receptionist)->postJson('/api/v1/opd/queue/tokens', [
            'department_id' => $this->generalDept->id,
            'patient_id' => $this->patient1->id,
            'priority' => 'normal',
        ]);

        $tokenNextDay->assertStatus(201)
            ->assertJsonPath('data.token_code', 'GEN-001')
            ->assertJsonPath('data.token_number', 1);
    }

    /**
     * Test 6: Queue call next and real-time waiting room display feed.
     */
    public function test_queue_calling_and_waiting_room_display(): void
    {
        // Issue token
        $this->opdRequest($this->receptionist)->postJson('/api/v1/opd/queue/tokens', [
            'department_id' => $this->generalDept->id,
            'patient_id' => $this->patient1->id,
            'priority' => 'normal',
        ])->assertStatus(201);

        // Doctor calls next patient into Room 102
        $callResponse = $this->opdRequest($this->doctor1)->postJson('/api/v1/opd/queue/call-next', [
            'department_id' => $this->generalDept->id,
            'counter_room' => 'Room 102',
        ]);

        $callResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'called')
            ->assertJsonPath('data.counter_room', 'Room 102');

        // Waiting room display query
        $displayResponse = $this->opdRequest($this->receptionist)
            ->getJson("/api/v1/opd/queue/display?department_id={$this->generalDept->id}");

        $displayResponse->assertStatus(200)
            ->assertJsonPath('data.calling.0.token_code', 'GEN-001')
            ->assertJsonPath('data.calling.0.counter_room', 'Room 102');
    }

    /**
     * Test 7: SOAP notes drafting, sign-off immutability, and versioned amendments.
     */
    public function test_soap_notes_immutability_and_versioned_amendment(): void
    {
        // 1. Doctor creates draft SOAP note
        $draftResponse = $this->opdRequest($this->doctor1)->postJson('/api/v1/opd/soap-notes', [
            'patient_id' => $this->patient1->id,
            'chief_complaint' => 'Patient complains of acute migraine and photophobia.',
            'history_of_presenting_illness' => 'Onset 2 days ago, throbbing pain.',
            'vitals' => [
                'bp_systolic' => 120,
                'bp_diastolic' => 80,
                'heart_rate' => 72,
            ],
            'physical_examination' => 'Pupils equally reactive, no focal neurological deficit.',
            'provisional_diagnosis' => 'Tension headache vs mild migraine.',
            'treatment_plan' => 'Prescribed Ibuprofen 400mg TID. Rest in dark room.',
            'sign_off_now' => false,
        ]);

        $draftResponse->assertStatus(201)
            ->assertJsonPath('data.is_signed_off', false)
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.notes_status', 'draft');

        $noteId = $draftResponse->json('data.id');

        // 2. Doctor updates draft SOAP note (allowed while not signed off)
        $updateResponse = $this->opdRequest($this->doctor1)->putJson("/api/v1/opd/soap-notes/{$noteId}", [
            'patient_id' => $this->patient1->id,
            'chief_complaint' => 'Patient complains of acute migraine and severe nausea.',
            'provisional_diagnosis' => 'Migraine with aura.',
            'treatment_plan' => 'Prescribed Sumatriptan 50mg. Advise hydration.',
        ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.chief_complaint', 'Patient complains of acute migraine and severe nausea.');

        // 3. Doctor signs off the SOAP note -> Seals it legally
        $signOffResponse = $this->opdRequest($this->doctor1)
            ->postJson("/api/v1/opd/soap-notes/{$noteId}/sign-off");

        $signOffResponse->assertStatus(200)
            ->assertJsonPath('data.is_signed_off', true)
            ->assertJsonPath('data.notes_status', 'signed_off');

        // 4. Attempting to directly modify a signed-off note MUST be rejected (immutability)
        $tamperAttempt = $this->opdRequest($this->doctor1)->putJson("/api/v1/opd/soap-notes/{$noteId}", [
            'patient_id' => $this->patient1->id,
            'chief_complaint' => 'Tampered chief complaint',
            'provisional_diagnosis' => 'Tampered diagnosis',
            'treatment_plan' => 'Tampered plan',
        ]);

        $tamperAttempt->assertStatus(422)
            ->assertJsonPath('code', 'NOTE_LOCKED_IMMUTABLE');

        // Verify the database still has untouched original text
        $noteInDb = ConsultationNote::findOrFail($noteId);
        $this->assertTrue($noteInDb->is_signed_off);
        $this->assertEquals('Migraine with aura.', $noteInDb->provisional_diagnosis);

        // 5. Legal amendment creates version 2 with audit trail
        $amendResponse = $this->opdRequest($this->doctor1)->postJson("/api/v1/opd/soap-notes/{$noteId}/amend", [
            'treatment_plan' => 'Discontinue Sumatriptan if pain score < 2.',
            'amendment_reason' => 'Patient telephoned 3 hours post-treatment with update on symptoms.',
        ]);

        $amendResponse->assertStatus(201)
            ->assertJsonPath('data.version', 2)
            ->assertJsonPath('data.is_signed_off', true)
            ->assertJsonPath('data.parent_note_id', $noteId)
            ->assertJsonPath('data.amendment_reason', 'Patient telephoned 3 hours post-treatment with update on symptoms.');

        // Verify original note status marked as 'amended'
        $this->assertEquals('amended', $noteInDb->fresh()->notes_status);
    }

    /**
     * Test 8: Inter-department and external referrals.
     */
    public function test_referral_creation_and_status_transition(): void
    {
        // 1. Internal referral from General OPD to Pediatrics
        $internalReferral = $this->opdRequest($this->doctor1)->postJson('/api/v1/opd/referrals', [
            'patient_id' => $this->patient1->id,
            'referral_type' => 'internal_department',
            'from_department_id' => $this->generalDept->id,
            'to_department_id' => $this->pediatricsDept->id,
            'priority' => 'urgent',
            'reason_for_referral' => 'Specialized pediatric consultation required',
            'clinical_summary' => 'Patient requires detailed pediatric endocrine review',
        ]);

        $internalReferral->assertStatus(201)
            ->assertJsonPath('data.referral_type', 'internal_department')
            ->assertJsonPath('data.priority', 'urgent')
            ->assertJsonPath('data.status', 'pending');

        $referralId = $internalReferral->json('data.id');

        // Accept the referral
        $this->opdRequest($this->doctor2)
            ->patchJson("/api/v1/opd/referrals/{$referralId}/status", ['status' => 'accepted'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'accepted');

        // 2. External referral to specialized facility
        $externalReferral = $this->opdRequest($this->doctor1)->postJson('/api/v1/opd/referrals', [
            'patient_id' => $this->patient2->id,
            'referral_type' => 'external_facility',
            'external_facility_name' => 'National Neurosurgery Institute',
            'external_specialist_name' => 'Dr. Charles Xavier',
            'external_contact' => '+18005550199',
            'priority' => 'emergency',
            'reason_for_referral' => 'Neurosurgical evaluation needed',
        ]);

        $externalReferral->assertStatus(201)
            ->assertJsonPath('data.referral_type', 'external_facility')
            ->assertJsonPath('data.external_facility_name', 'National Neurosurgery Institute');
    }
}
