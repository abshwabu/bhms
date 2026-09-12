<?php

namespace Tests\Feature;

use App\Domain\Emergency\Models\Ambulance;
use App\Domain\Emergency\Models\AmbulanceDispatch;
use App\Domain\Emergency\Models\EmergencyBedAllocation;
use App\Domain\Emergency\Models\EmergencyCase;
use App\Domain\IPD\Models\Bed;
use App\Domain\IPD\Models\Ward;
use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmergencyDomainTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;
    protected Branch $branch;
    protected User $user;
    protected Ward $erWard;
    protected Bed $resusBed;
    protected Bed $standardBed;

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

        $this->erWard = Ward::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'name' => 'Emergency Department',
            'code' => 'ED-TEST',
            'ward_type' => 'icu',
            'capacity' => 10,
            'is_active' => true,
        ]);

        $this->resusBed = Bed::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'ward_id' => $this->erWard->id,
            'bed_number' => 'RESUS-01',
            'bed_type' => 'icu_ventilator',
            'status' => 'available',
            'is_active' => true,
        ]);

        $this->standardBed = Bed::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'ward_id' => $this->erWard->id,
            'bed_number' => 'ER-02',
            'bed_type' => 'standard',
            'status' => 'available',
            'is_active' => true,
        ]);
    }

    /**
     * Acceptance criterion 1:
     * Triage severity level determines queue priority automatically.
     */
    public function test_triage_severity_level_determines_queue_priority_automatically(): void
    {
        // 1. Create ESI 3 case arriving at 10:00
        $caseUrgent = EmergencyCase::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'case_number' => 'ER-TEST-003',
            'patient_temp_name' => 'Patient ESI-3',
            'arrival_datetime' => Carbon::now()->subMinutes(30),
            'chief_complaint' => 'Moderate abdominal pain',
            'initial_triage_esi' => 3,
            'current_esi_level' => 3,
            'priority_score' => 60,
            'status' => 'triaged',
        ]);

        // 2. Create ESI 1 (Immediate Resuscitation) case arriving 5 mins ago
        $caseCritical = EmergencyCase::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'case_number' => 'ER-TEST-001',
            'patient_temp_name' => 'Patient ESI-1 Critical',
            'arrival_datetime' => Carbon::now()->subMinutes(5),
            'chief_complaint' => 'Cardiac arrest, CPR in progress',
            'initial_triage_esi' => 1,
            'current_esi_level' => 1,
            'priority_score' => 100,
            'status' => 'triaged',
        ]);

        // 3. Create ESI 2 (Emergent) case arriving 15 mins ago
        $caseEmergent = EmergencyCase::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'case_number' => 'ER-TEST-002',
            'patient_temp_name' => 'Patient ESI-2 Emergent',
            'arrival_datetime' => Carbon::now()->subMinutes(15),
            'chief_complaint' => 'Severe acute stroke symptoms',
            'initial_triage_esi' => 2,
            'current_esi_level' => 2,
            'priority_score' => 80,
            'status' => 'triaged',
        ]);

        // Query triage queue API
        $response = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->getJson("/api/v1/emergency/cases?branch_id={$this->branch->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $queue = $response->json('data');
        $this->assertCount(3, $queue);

        // First patient in queue MUST be the ESI 1 case despite arriving latest!
        $this->assertEquals('ER-TEST-001', $queue[0]['case_number']);
        $this->assertEquals(1, $queue[0]['current_esi_level']);

        // Second patient MUST be ESI 2
        $this->assertEquals('ER-TEST-002', $queue[1]['case_number']);
        $this->assertEquals(2, $queue[1]['current_esi_level']);

        // Third patient MUST be ESI 3
        $this->assertEquals('ER-TEST-003', $queue[2]['case_number']);
        $this->assertEquals(3, $queue[2]['current_esi_level']);
    }

    /**
     * Test structured triage assessment with danger zone vitals auto-escalation.
     */
    public function test_clinical_triage_with_danger_vitals_auto_escalation(): void
    {
        $case = EmergencyCase::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'case_number' => 'ER-TEST-004',
            'patient_temp_name' => 'Walk-in Patient',
            'arrival_datetime' => Carbon::now()->subMinutes(10),
            'chief_complaint' => 'Difficulty breathing and wheezing',
            'initial_triage_esi' => 4,
            'current_esi_level' => 4,
            'priority_score' => 40,
            'status' => 'registered',
        ]);

        // Triage input with danger vitals: SpO2 86%, Systolic 80
        $triagePayload = [
            'esi_level' => 3, // Nurse initially thought Level 3
            'triage_category' => 'respiratory',
            'vital_signs' => [
                'heart_rate' => 136,
                'bp_systolic' => 82,
                'bp_diastolic' => 54,
                'respiratory_rate' => 34,
                'spo2' => 87, // Severe Hypoxia!
                'temperature' => 37.8,
                'gcs' => 14,
            ],
            'red_flags' => ['severe_respiratory_distress'],
            'assessment_notes' => 'Patient in acute hypoxic distress with accessory muscle use.',
        ];

        $response = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson("/api/v1/emergency/cases/{$case->id}/triage", $triagePayload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.triage.is_danger_zone_vitals', true)
            ->assertJsonPath('data.triage.esi_level', 2) // Auto-escalated to Level 2!
            ->assertJsonPath('data.case.priority_score', 80);

        $case->refresh();
        $this->assertEquals(2, $case->current_esi_level);
        $this->assertEquals(80, $case->priority_score);
    }

    /**
     * Acceptance criterion 2:
     * Ambulance status (available/dispatched/en route/arrived) updates in near real-time.
     */
    public function test_ambulance_dispatch_lifecycle_and_real_time_status_updates(): void
    {
        $ambulance = Ambulance::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'vehicle_number' => 'AMB-TEST-01',
            'call_sign' => 'Rescue-99',
            'ambulance_type' => 'als',
            'model' => 'Ford Transit 350',
            'plate_number' => 'EMS-999',
            'status' => 'available',
        ]);

        $this->assertTrue($ambulance->is_available);

        // 1. Create Dispatch -> status becomes 'dispatched'
        $dispatchResponse = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson('/api/v1/emergency/dispatches', [
                'ambulance_id' => $ambulance->id,
                'caller_name' => 'Witness 911',
                'caller_phone' => '+1555999111',
                'pickup_address' => '100 Main St, Suite 4',
                'priority' => 'code_red',
                'nature_of_emergency' => 'Pedestrian struck by vehicle, unresponsive',
            ]);

        $dispatchResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'dispatched');

        $dispatchId = $dispatchResponse->json('data.id');
        $ambulance->refresh();
        $this->assertEquals('dispatched', $ambulance->status);

        // 2. Status update: en_route_scene
        $status1 = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson("/api/v1/emergency/dispatches/{$dispatchId}/status", [
                'status' => 'en_route_scene',
            ]);
        $status1->assertStatus(200)->assertJsonPath('data.status', 'en_route_scene');
        $ambulance->refresh();
        $this->assertEquals('en_route_scene', $ambulance->status);

        // 3. Status update: at_scene
        $status2 = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson("/api/v1/emergency/dispatches/{$dispatchId}/status", [
                'status' => 'at_scene',
                'notes' => 'Patient packaged on spineboard, preparing transport.',
            ]);
        $status2->assertStatus(200)->assertJsonPath('data.status', 'at_scene');
        $ambulance->refresh();
        $this->assertEquals('at_scene', $ambulance->status);

        // 4. Status update: en_route_hospital
        $status3 = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson("/api/v1/emergency/dispatches/{$dispatchId}/status", [
                'status' => 'en_route_hospital',
            ]);
        $status3->assertStatus(200)->assertJsonPath('data.status', 'en_route_hospital');
        $ambulance->refresh();
        $this->assertEquals('en_route_hospital', $ambulance->status);

        // 5. Status update: arrived_hospital
        $status4 = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson("/api/v1/emergency/dispatches/{$dispatchId}/status", [
                'status' => 'arrived_hospital',
            ]);
        $status4->assertStatus(200)->assertJsonPath('data.status', 'arrived_hospital');
        $ambulance->refresh();
        $this->assertEquals('arrived_hospital', $ambulance->status);

        // 6. Complete mission -> Ambulance automatically reverts to available!
        $status5 = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson("/api/v1/emergency/dispatches/{$dispatchId}/status", [
                'status' => 'completed',
                'notes' => 'Patient handed over to ER Trauma Bay 1.',
            ]);
        $status5->assertStatus(200)->assertJsonPath('data.status', 'completed');
        $ambulance->refresh();
        $this->assertEquals('available', $ambulance->status);
        $this->assertTrue($ambulance->is_available);
    }

    /**
     * Test live ambulance GPS telemetry recording.
     */
    public function test_ambulance_gps_telemetry_recording(): void
    {
        $ambulance = Ambulance::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'vehicle_number' => 'AMB-TEST-02',
            'call_sign' => 'Alpha-1',
            'ambulance_type' => 'als',
            'model' => 'Sprinter',
            'plate_number' => 'EMS-123',
            'status' => 'en_route_hospital',
        ]);

        $response = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson("/api/v1/emergency/ambulances/{$ambulance->id}/telemetry", [
                'latitude' => 40.730610,
                'longitude' => -73.935242,
                'speed_kmh' => 74.5,
                'heading' => 180.0,
                'fuel_percentage' => 88,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.current_latitude', 40.73061)
            ->assertJsonPath('data.current_longitude', -73.935242)
            ->assertJsonPath('data.speed_kmh', 74.5);

        $ambulance->refresh();
        $this->assertEquals(40.730610, $ambulance->current_latitude);
        $this->assertEquals(74.5, $ambulance->speed_kmh);
        $this->assertNotNull($ambulance->last_telemetry_at);
    }

    /**
     * Acceptance criterion 3:
     * Emergency bed allocation can override standard bed queue with a logged justification.
     */
    public function test_emergency_bed_allocation_with_priority_override_and_justification(): void
    {
        // 1. Bed is already marked occupied (e.g. standard inpatient reservation)
        $this->resusBed->update(['status' => 'occupied']);

        $case = EmergencyCase::create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'case_number' => 'ER-TEST-005',
            'patient_temp_name' => 'Gunshot Wound Trauma Patient',
            'arrival_datetime' => Carbon::now()->subMinutes(2),
            'chief_complaint' => 'Thoracic penetrating trauma, hemorrhagic shock',
            'initial_triage_esi' => 1,
            'current_esi_level' => 1,
            'priority_score' => 100,
            'status' => 'triaged',
        ]);

        // Attempt 1: Standard allocation on occupied bed without override -> Fails with 422
        $failResponse = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson("/api/v1/emergency/cases/{$case->id}/allocate-bed", [
                'bed_id' => $this->resusBed->id,
                'is_override' => false,
            ]);

        $failResponse->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'BED_ALLOCATION_ERROR');

        // Attempt 2: Priority override requested but justification is missing -> Fails with 422
        $noJustificationResponse = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson("/api/v1/emergency/cases/{$case->id}/allocate-bed", [
                'bed_id' => $this->resusBed->id,
                'is_override' => true,
                'override_reason' => '',
            ]);

        $noJustificationResponse->assertStatus(422)
            ->assertJsonPath('success', false);

        // Attempt 3: Priority override with logged clinical justification -> Succeeds with 201
        $justification = 'Critical penetrating thoracic trauma with active exsanguination; immediate operative thoracotomy setup required. Overriding scheduled elective transfer.';

        $overrideResponse = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson("/api/v1/emergency/cases/{$case->id}/allocate-bed", [
                'bed_id' => $this->resusBed->id,
                'is_override' => true,
                'override_reason' => $justification,
            ]);

        $overrideResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.allocation.is_override', true)
            ->assertJsonPath('data.allocation.override_reason', $justification)
            ->assertJsonPath('data.case.assigned_bed_id', $this->resusBed->id)
            ->assertJsonPath('data.case.status', 'bed_assigned');

        // Verify allocation record is logged in database
        $allocation = EmergencyBedAllocation::where('emergency_case_id', $case->id)
            ->where('bed_id', $this->resusBed->id)
            ->first();

        $this->assertNotNull($allocation);
        $this->assertTrue($allocation->is_override);
        $this->assertEquals($justification, $allocation->override_reason);

        // 4. Release bed when patient moves to OR
        $releaseResponse = $this->withHeader('X-Branch-ID', $this->branch->id)
            ->postJson("/api/v1/emergency/cases/{$case->id}/release-bed", [
                'notes' => 'Patient transferred to Operating Room Suite 2.',
            ]);

        $releaseResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->resusBed->refresh();
        $this->assertEquals('available', $this->resusBed->status);

        $case->refresh();
        $this->assertNull($case->assigned_bed_id);
    }
}
