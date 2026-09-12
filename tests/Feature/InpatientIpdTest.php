<?php

namespace Tests\Feature;

use App\Domain\IPD\Models\Admission;
use App\Domain\IPD\Models\Bed;
use App\Domain\IPD\Models\BedTransfer;
use App\Domain\IPD\Models\DischargeSummary;
use App\Domain\IPD\Models\MedicationAdministration;
use App\Domain\IPD\Models\VitalsLog;
use App\Domain\IPD\Models\Ward;
use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InpatientIpdTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;
    protected Branch $branch;
    protected User $doctor;
    protected User $nurse;
    protected Ward $medicalWard;
    protected Ward $icuWard;
    protected Bed $bed101;
    protected Bed $bed102;
    protected Bed $bedIcu1;
    protected Patient $patient1;
    protected Patient $patient2;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Organization & Branch
        $this->org = Organization::create([
            'id' => (string) Str::uuid(),
            'name' => 'St. Jude Health System',
            'code' => 'SJHS-' . Str::random(4),
            'tax_number' => 'TAX-5544',
        ]);

        $this->branch = Branch::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'name' => 'St. Jude Memorial Hospital',
            'code' => 'MEMORIAL',
        ]);

        // 2. Staff Users
        $this->doctor = User::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Dr. Gregory House',
            'email' => 'house_' . Str::random(5) . '@stjude.local',
            'password' => bcrypt('password123'),
        ]);

        $this->nurse = User::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Nurse Jackie Peyton',
            'email' => 'jackie_' . Str::random(5) . '@stjude.local',
            'password' => bcrypt('password123'),
        ]);

        // 3. Wards
        $this->medicalWard = Ward::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'name' => 'General Medical Ward',
            'code' => 'GMW',
            'ward_type' => 'general',
            'floor_number' => '2',
            'capacity' => 10,
            'daily_rate' => 150.00,
            'is_active' => true,
        ]);

        $this->icuWard = Ward::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'name' => 'Intensive Care Unit',
            'code' => 'ICU',
            'ward_type' => 'icu',
            'floor_number' => '3',
            'capacity' => 4,
            'daily_rate' => 500.00,
            'is_active' => true,
        ]);

        // 4. Beds
        $this->bed101 = Bed::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'ward_id' => $this->medicalWard->id,
            'bed_number' => 'GMW-101',
            'bed_type' => 'standard',
            'status' => 'available',
            'is_active' => true,
        ]);

        $this->bed102 = Bed::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'ward_id' => $this->medicalWard->id,
            'bed_number' => 'GMW-102',
            'bed_type' => 'standard',
            'status' => 'available',
            'is_active' => true,
        ]);

        $this->bedIcu1 = Bed::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'ward_id' => $this->icuWard->id,
            'bed_number' => 'ICU-01',
            'bed_type' => 'icu_ventilator',
            'status' => 'available',
            'is_active' => true,
        ]);

        // 5. Patients
        $this->patient1 = Patient::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'mrn' => 'MRN-2026-MEMORIAL-0001',
            'registration_type' => 'walk_in',
            'first_name' => 'John',
            'last_name' => 'Watson',
            'date_of_birth' => '1982-07-07',
            'gender' => 'male',
            'phone' => '+15551234567',
        ]);

        $this->patient2 = Patient::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'mrn' => 'MRN-2026-MEMORIAL-0002',
            'registration_type' => 'walk_in',
            'first_name' => 'Sherlock',
            'last_name' => 'Holmes',
            'date_of_birth' => '1980-01-06',
            'gender' => 'male',
            'phone' => '+15559876543',
        ]);
    }

    protected function ipdRequest(User $user)
    {
        return $this->actingAs($user, 'sanctum')
            ->withHeader('X-Branch-ID', $this->branch->id);
    }

    /**
     * Acceptance Criteria 1: A bed cannot be assigned to two active admissions simultaneously.
     */
    public function test_bed_cannot_be_assigned_to_two_active_admissions_simultaneously(): void
    {
        // 1. Admit Patient 1 into Bed GMW-101
        $admitResponse = $this->ipdRequest($this->doctor)->postJson('/api/v1/ipd/admissions', [
            'patient_id' => $this->patient1->id,
            'bed_id' => $this->bed101->id,
            'admitting_doctor_id' => $this->doctor->id,
            'admission_type' => 'emergency',
            'admitting_diagnosis' => 'Community-acquired bacterial pneumonia',
            'chief_complaint' => 'Shortness of breath and fever for 3 days',
            'initial_vitals' => [
                'bp_systolic' => 110,
                'bp_diastolic' => 70,
                'heart_rate' => 96,
                'temperature_c' => 38.8,
                'spo2' => 91.0,
            ],
        ]);

        $admitResponse->assertStatus(201)
            ->assertJsonPath('data.status', 'admitted')
            ->assertJsonPath('data.bed_id', $this->bed101->id)
            ->assertJsonPath('data.admitted_by', $this->doctor->id);

        $this->assertEquals('occupied', $this->bed101->fresh()->status);

        // 2. Attempt to admit Patient 2 into the SAME Bed (GMW-101) while Patient 1 is still active
        $conflictResponse = $this->ipdRequest($this->doctor)->postJson('/api/v1/ipd/admissions', [
            'patient_id' => $this->patient2->id,
            'bed_id' => $this->bed101->id,
            'admitting_doctor_id' => $this->doctor->id,
            'admission_type' => 'elective',
            'admitting_diagnosis' => 'Acute appendicitis',
        ]);

        $conflictResponse->assertStatus(422)
            ->assertJsonPath('code', 'BED_OCCUPIED_OR_UNAVAILABLE');

        // Verify Patient 2 is NOT admitted in database
        $this->assertDatabaseMissing('admissions', [
            'patient_id' => $this->patient2->id,
            'bed_id' => $this->bed101->id,
            'status' => 'admitted',
        ]);

        // 3. Admitting Patient 2 into a different free bed (GMW-102) succeeds
        $secondBedResponse = $this->ipdRequest($this->doctor)->postJson('/api/v1/ipd/admissions', [
            'patient_id' => $this->patient2->id,
            'bed_id' => $this->bed102->id,
            'admitting_doctor_id' => $this->doctor->id,
            'admission_type' => 'elective',
            'admitting_diagnosis' => 'Acute appendicitis',
        ]);

        $secondBedResponse->assertStatus(201)
            ->assertJsonPath('data.status', 'admitted')
            ->assertJsonPath('data.bed_id', $this->bed102->id);

        $this->assertEquals('occupied', $this->bed102->fresh()->status);
    }

    /**
     * Acceptance Criteria 2: Every bed transfer and status change is logged with timestamp and staff ID.
     */
    public function test_bed_transfer_and_status_changes_logged_with_timestamp_and_staff_id(): void
    {
        // 1. Admit Patient 1 into GMW-101
        $admit = $this->ipdRequest($this->nurse)->postJson('/api/v1/ipd/admissions', [
            'patient_id' => $this->patient1->id,
            'bed_id' => $this->bed101->id,
            'admitting_doctor_id' => $this->doctor->id,
            'admitting_diagnosis' => 'Severe Sepsis',
        ])->json('data');

        $admissionId = $admit['id'];
        $this->assertEquals($this->nurse->id, $admit['admitted_by']);
        $this->assertNotNull($admit['admitted_at']);

        // 2. Transfer Patient from GMW-101 to ICU-01 due to clinical deterioration
        $transferResponse = $this->ipdRequest($this->nurse)->postJson("/api/v1/ipd/admissions/{$admissionId}/transfer", [
            'to_bed_id' => $this->bedIcu1->id,
            'reason' => 'Patient deteriorated, requiring mechanical ventilation in ICU',
        ]);

        $transferResponse->assertStatus(201)
            ->assertJsonPath('data.from_bed_id', $this->bed101->id)
            ->assertJsonPath('data.to_bed_id', $this->bedIcu1->id)
            ->assertJsonPath('data.transferred_by', $this->nurse->id)
            ->assertJsonPath('data.status', 'completed');

        // Check Audit Ledger table: bed_transfers
        $this->assertDatabaseHas('bed_transfers', [
            'admission_id' => $admissionId,
            'patient_id' => $this->patient1->id,
            'from_bed_id' => $this->bed101->id,
            'to_bed_id' => $this->bedIcu1->id,
            'transferred_by' => $this->nurse->id,
            'reason' => 'Patient deteriorated, requiring mechanical ventilation in ICU',
        ]);

        $transferRecord = BedTransfer::where('admission_id', $admissionId)->first();
        $this->assertNotNull($transferRecord->transferred_at);
        $this->assertEquals($this->nurse->id, $transferRecord->transferred_by);

        // Verify Bed statuses after transfer: Old bed is now 'cleaning', New bed is 'occupied'
        $this->assertEquals('cleaning', $this->bed101->fresh()->status);
        $this->assertEquals('occupied', $this->bedIcu1->fresh()->status);

        // Verify admission now references the new bed and ward
        $updatedAdmission = Admission::findOrFail($admissionId);
        $this->assertEquals($this->bedIcu1->id, $updatedAdmission->bed_id);
        $this->assertEquals($this->icuWard->id, $updatedAdmission->ward_id);

        // 3. Status change check upon discharge: Logs staff ID and timestamp
        $dischargeResponse = $this->ipdRequest($this->doctor)->postJson("/api/v1/ipd/admissions/{$admissionId}/discharge", [
            'discharge_type' => 'regular',
            'discharge_condition' => 'improved',
            'primary_diagnosis' => 'Resolved Sepsis secondary to pneumonia',
            'hospital_course_summary' => '48 hours in ICU, transferred to stepdown, completed antibiotic course.',
        ]);

        $dischargeResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'discharged')
            ->assertJsonPath('data.discharged_by', $this->doctor->id);

        $this->assertNotNull($updatedAdmission->fresh()->discharged_at);
        $this->assertEquals($this->doctor->id, $updatedAdmission->fresh()->discharged_by);
        $this->assertEquals('cleaning', $this->bedIcu1->fresh()->status);
    }

    /**
     * Acceptance Criteria 3: Discharge summary pulls admission date, diagnoses, procedures, and medications automatically.
     */
    public function test_discharge_summary_auto_populates_stay_records(): void
    {
        $admittedAt = Carbon::now()->subDays(4);

        // 1. Admit patient with specific admitting and secondary diagnoses
        $admission = Admission::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'admission_number' => 'ADM-TEST-999',
            'patient_id' => $this->patient1->id,
            'ward_id' => $this->medicalWard->id,
            'bed_id' => $this->bed101->id,
            'admitting_doctor_id' => $this->doctor->id,
            'admitted_by' => $this->nurse->id,
            'status' => 'admitted',
            'admitted_at' => $admittedAt,
            'admitting_diagnosis' => 'Acute Pyelonephritis',
            'primary_diagnosis' => 'Acute Pyelonephritis with Sepsis',
            'secondary_diagnoses' => ['Type 2 Diabetes Mellitus', 'Hypertension'],
            'procedures_performed' => ['Renal Ultrasound', 'Peripheral IV Cannulation'],
        ]);

        $this->bed101->update(['status' => 'occupied']);

        // 2. Nurse administers medications during inpatient stay
        $this->ipdRequest($this->nurse)->postJson("/api/v1/ipd/admissions/{$admission->id}/medications", [
            'medication_name' => 'Ceftriaxone Sodium',
            'dosage' => '1g IV once daily',
            'route' => 'iv',
            'status' => 'given',
        ])->assertStatus(201);

        $this->ipdRequest($this->nurse)->postJson("/api/v1/ipd/admissions/{$admission->id}/medications", [
            'medication_name' => 'Paracetamol',
            'dosage' => '1000mg PO PRN',
            'route' => 'oral',
            'status' => 'given',
        ])->assertStatus(201);

        // 3. Doctor performs discharge
        $dischargeResponse = $this->ipdRequest($this->doctor)->postJson("/api/v1/ipd/admissions/{$admission->id}/discharge", [
            'discharge_type' => 'regular',
            'discharge_condition' => 'cured',
            'hospital_course_summary' => 'Treated with IV Ceftriaxone for 96 hours. Afebrile for 48 hours, inflammatory markers normalized.',
            'follow_up_instructions' => 'Complete oral Cefixime 400mg daily for 5 days. Repeat urine culture in 10 days.',
            'follow_up_date' => Carbon::now()->addDays(10)->toDateString(),
        ]);

        $dischargeResponse->assertStatus(200);

        // 4. Query Discharge Summary and verify ALL records are auto-pulled
        $summaryResponse = $this->ipdRequest($this->doctor)->getJson("/api/v1/ipd/admissions/{$admission->id}/discharge-summary");

        $summaryResponse->assertStatus(200)
            ->assertJsonPath('data.admission_id', $admission->id)
            ->assertJsonPath('data.primary_diagnosis', 'Acute Pyelonephritis with Sepsis')
            ->assertJsonPath('data.secondary_diagnoses.0', 'Type 2 Diabetes Mellitus')
            ->assertJsonPath('data.procedures_performed.0', 'Renal Ultrasound')
            ->assertJsonPath('data.discharge_condition', 'cured');

        // Check auto-populated admission and discharge dates
        $data = $summaryResponse->json('data');
        $this->assertEquals($admittedAt->toIso8601String(), Carbon::parse($data['admission_date'])->toIso8601String());
        $this->assertNotNull($data['discharge_date']);

        // Check auto-populated medications administered during the stay
        $meds = $data['medications_at_discharge'];
        $medNames = array_column($meds, 'medication_name');
        $this->assertContains('Ceftriaxone Sodium', $medNames);
        $this->assertContains('Paracetamol', $medNames);

        // 5. Finalize and seal the discharge summary
        $finalizeResponse = $this->ipdRequest($this->doctor)->postJson("/api/v1/ipd/admissions/{$admission->id}/discharge-summary/finalize");

        $finalizeResponse->assertStatus(200)
            ->assertJsonPath('data.is_finalized', true);

        $this->assertTrue(DischargeSummary::where('admission_id', $admission->id)->value('is_finalized'));
    }

    /**
     * Test 4: Nursing station dashboard vitals logging and charting.
     */
    public function test_nursing_station_vitals_logging_and_charting(): void
    {
        $admission = Admission::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $this->org->id,
            'branch_id' => $this->branch->id,
            'admission_number' => 'ADM-NURSE-001',
            'patient_id' => $this->patient1->id,
            'ward_id' => $this->medicalWard->id,
            'bed_id' => $this->bed101->id,
            'admitting_doctor_id' => $this->doctor->id,
            'admitted_by' => $this->nurse->id,
            'status' => 'admitted',
            'admitted_at' => now(),
            'admitting_diagnosis' => 'Hypertensive urgency',
        ]);

        // Nurse logs vitals
        $vitalsResponse = $this->ipdRequest($this->nurse)->postJson("/api/v1/ipd/admissions/{$admission->id}/vitals", [
            'bp_systolic' => 165,
            'bp_diastolic' => 105,
            'heart_rate' => 88,
            'respiratory_rate' => 18,
            'temperature_c' => 36.7,
            'spo2' => 98.0,
            'pain_score' => 2,
            'consciousness_level' => 'alert',
            'nursing_notes' => 'Patient administered oral Labetalol. Resting comfortably in Fowler position.',
        ]);

        $vitalsResponse->assertStatus(201)
            ->assertJsonPath('data.bp_systolic', 165)
            ->assertJsonPath('data.bp_diastolic', 105)
            ->assertJsonPath('data.bp_display', '165/105 mmHg');

        // Fetch Nursing Chart
        $chartResponse = $this->ipdRequest($this->nurse)->getJson("/api/v1/ipd/admissions/{$admission->id}/chart");

        $chartResponse->assertStatus(200)
            ->assertJsonCount(1, 'data.vitals_history')
            ->assertJsonPath('data.latest_vitals.bp_systolic', 165);
    }

    /**
     * Test 5: Inpatient Bed Occupancy & Average Length of Stay (ALOS) Analytics.
     */
    public function test_bed_occupancy_and_alos_analytics(): void
    {
        // Medical Ward has 2 beds (bed101, bed102). ICU has 1 bed (bedIcu1). Total = 3 beds.
        // Admit patient into bed101 (Occupied: 1, Available: 2)
        $this->ipdRequest($this->doctor)->postJson('/api/v1/ipd/admissions', [
            'patient_id' => $this->patient1->id,
            'bed_id' => $this->bed101->id,
            'admitting_doctor_id' => $this->doctor->id,
            'admitting_diagnosis' => 'Acute observation',
        ])->assertStatus(201);

        $analyticsResponse = $this->ipdRequest($this->doctor)->getJson('/api/v1/ipd/analytics/occupancy');

        $analyticsResponse->assertStatus(200)
            ->assertJsonPath('data.total_beds', 3)
            ->assertJsonPath('data.occupied_beds', 1)
            ->assertJsonPath('data.available_beds', 2)
            ->assertJsonPath('data.current_inpatient_census', 1)
            ->assertJsonPath('data.occupancy_rate_percent', 33.3);

        // Visual Bed Map endpoint
        $bedMapResponse = $this->ipdRequest($this->doctor)->getJson('/api/v1/ipd/bed-map');

        $bedMapResponse->assertStatus(200);
        $wards = $bedMapResponse->json('data');
        $this->assertCount(2, $wards);
    }
}
