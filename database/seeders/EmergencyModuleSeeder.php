<?php

namespace Database\Seeders;

use App\Domain\Emergency\Models\Ambulance;
use App\Domain\Emergency\Models\AmbulanceDispatch;
use App\Domain\Emergency\Models\EmergencyBedAllocation;
use App\Domain\Emergency\Models\EmergencyCase;
use App\Domain\Emergency\Models\TriageRecord;
use App\Domain\IPD\Models\Bed;
use App\Domain\IPD\Models\Ward;
use App\Domain\Patient\Models\Patient;
use App\Domain\Shared\Models\Branch;
use App\Domain\Shared\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EmergencyModuleSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::first();
        $branch = Branch::first();
        $user = User::first();
        $patient = Patient::first();

        if (!$organization || !$branch) {
            return;
        }

        // 1. Ensure Emergency Ward and Acute Trauma Bays exist in beds table
        $erWard = Ward::firstOrCreate(
            ['branch_id' => $branch->id, 'code' => 'ER-DEPT'],
            [
                'organization_id' => $organization->id,
                'name' => 'Emergency & Trauma Center',
                'ward_type' => 'icu',
                'floor_number' => 'Ground Floor',
                'capacity' => 12,
                'is_active' => true,
            ]
        );

        $resusBed = Bed::firstOrCreate(
            ['ward_id' => $erWard->id, 'bed_number' => 'RESUS-BAY-01'],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'bed_type' => 'icu_ventilator',
                'status' => 'available',
                'features' => ['ventilator' => true, 'cardiac_monitor' => true, 'defibrillator' => true, 'wall_oxygen' => true],
                'is_active' => true,
            ]
        );

        $traumaBed = Bed::firstOrCreate(
            ['ward_id' => $erWard->id, 'bed_number' => 'TRAUMA-BAY-02'],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'bed_type' => 'electric',
                'status' => 'available',
                'features' => ['cardiac_monitor' => true, 'wall_oxygen' => true, 'suction' => true],
                'is_active' => true,
            ]
        );

        $erBed3 = Bed::firstOrCreate(
            ['ward_id' => $erWard->id, 'bed_number' => 'ER-BED-03'],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'bed_type' => 'standard',
                'status' => 'available',
                'features' => ['wall_oxygen' => true],
                'is_active' => true,
            ]
        );

        // 2. Seed Ambulances Fleet with GPS Telemetry
        $amb1 = Ambulance::updateOrCreate(
            ['branch_id' => $branch->id, 'vehicle_number' => 'AMB-01'],
            [
                'organization_id' => $organization->id,
                'call_sign' => 'Medic-1',
                'ambulance_type' => 'als',
                'model' => 'Ford Transit 350HD High-Roof',
                'plate_number' => 'EMS-9021',
                'status' => 'en_route_hospital',
                'equipment' => [
                    'ventilator' => 'Hamilton-T1 Transport',
                    'defibrillator' => 'ZOLL X-Series Advanced',
                    'cardiac_monitor' => true,
                    'suction' => true,
                    'trauma_kit' => true,
                    'lucas_cpr' => true,
                ],
                'current_latitude' => 40.7142,
                'current_longitude' => -74.0064,
                'heading' => 45.0,
                'speed_kmh' => 62.5,
                'fuel_percentage' => 84,
                'last_telemetry_at' => Carbon::now()->subSeconds(30),
                'assigned_driver_name' => 'Michael Torres',
                'assigned_paramedic_name' => 'Sarah Connor, NRP',
                'notes' => 'Primary ALS unit equipped with automated chest compressor.',
            ]
        );

        $amb2 = Ambulance::updateOrCreate(
            ['branch_id' => $branch->id, 'vehicle_number' => 'AMB-02'],
            [
                'organization_id' => $organization->id,
                'call_sign' => 'Medic-2',
                'ambulance_type' => 'als',
                'model' => 'Mercedes-Benz Sprinter 3500XD',
                'plate_number' => 'EMS-9022',
                'status' => 'available',
                'equipment' => [
                    'ventilator' => true,
                    'defibrillator' => 'Physio-Control LIFEPAK 15',
                    'cardiac_monitor' => true,
                    'suction' => true,
                    'trauma_kit' => true,
                ],
                'current_latitude' => 40.7128,
                'current_longitude' => -74.0060,
                'heading' => 0.0,
                'speed_kmh' => 0.0,
                'fuel_percentage' => 95,
                'last_telemetry_at' => Carbon::now()->subMinutes(5),
                'assigned_driver_name' => 'John Miller',
                'assigned_paramedic_name' => 'Rachel Adams, NRP',
                'notes' => 'Stationed at hospital emergency ambulance bay, ready for immediate dispatch.',
            ]
        );

        $amb3 = Ambulance::updateOrCreate(
            ['branch_id' => $branch->id, 'vehicle_number' => 'AMB-03'],
            [
                'organization_id' => $organization->id,
                'call_sign' => 'Rescue-3',
                'ambulance_type' => 'cct',
                'model' => 'Freightliner M2 Critical Care Mobile ICU',
                'plate_number' => 'EMS-9023',
                'status' => 'at_scene',
                'equipment' => [
                    'ecmo_transport_mount' => true,
                    'icu_infusion_pumps' => 4,
                    'ventilator' => 'Servo-air mobile',
                    'blood_warmer' => true,
                ],
                'current_latitude' => 40.7280,
                'current_longitude' => -73.9940,
                'heading' => 120.0,
                'speed_kmh' => 0.0,
                'fuel_percentage' => 70,
                'last_telemetry_at' => Carbon::now()->subMinutes(2),
                'assigned_driver_name' => 'Carlos Vega',
                'assigned_paramedic_name' => 'David Chen, CCEMT-P',
                'notes' => 'Critical care transport mobile unit currently on scene stabilizing high-acuity trauma.',
            ]
        );

        $amb4 = Ambulance::updateOrCreate(
            ['branch_id' => $branch->id, 'vehicle_number' => 'AMB-04'],
            [
                'organization_id' => $organization->id,
                'call_sign' => 'Alpha-4',
                'ambulance_type' => 'bls',
                'model' => 'Ford E-Series 350',
                'plate_number' => 'EMS-9024',
                'status' => 'maintenance',
                'equipment' => [
                    'aed' => true,
                    'suction' => true,
                    'oxygen_cylinders' => 2,
                ],
                'current_latitude' => 40.7100,
                'current_longitude' => -74.0090,
                'speed_kmh' => 0.0,
                'fuel_percentage' => 40,
                'notes' => 'Scheduled fleet oil change and brake rotor inspection.',
            ]
        );

        // 3. Seed Ambulance Dispatches
        $now = Carbon::now();

        $disp1 = AmbulanceDispatch::updateOrCreate(
            ['dispatch_number' => 'DISP-2026-0001'],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'ambulance_id' => $amb1->id,
                'caller_name' => 'NYC 911 Emergency Communications',
                'caller_phone' => '+1 (555) 911-0000',
                'pickup_address' => '350 5th Avenue (Empire State Building), Manhattan, NY',
                'pickup_latitude' => 40.7484,
                'pickup_longitude' => -73.9857,
                'destination_address' => 'Metro General Hospital Emergency Bay 1',
                'destination_latitude' => 40.7128,
                'destination_longitude' => -74.0060,
                'priority' => 'code_red',
                'nature_of_emergency' => '64-year-old male with acute onset substernal crushing chest pain, radiating to left arm. Profuse diaphoresis, marked ST-elevation on 12-lead ECG.',
                'status' => 'en_route_hospital',
                'patient_condition_notes' => '12-lead transmission confirms acute inferior-lateral STEMI. Patient loaded with Aspirin 324mg and SL NTG x2. En route lights and sirens, ETA 6 minutes.',
                'dispatched_by' => $user?->id,
                'dispatched_at' => $now->copy()->subMinutes(25),
                'en_route_scene_at' => $now->copy()->subMinutes(23),
                'arrived_scene_at' => $now->copy()->subMinutes(14),
                'departed_scene_at' => $now->copy()->subMinutes(6),
            ]
        );

        $disp2 = AmbulanceDispatch::updateOrCreate(
            ['dispatch_number' => 'DISP-2026-0002'],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'ambulance_id' => $amb3->id,
                'caller_name' => 'State Highway Patrol Unit 42',
                'caller_phone' => '+1 (555) 432-8899',
                'pickup_address' => 'FDR Drive Northbound near Exit 7',
                'pickup_latitude' => 40.7280,
                'pickup_longitude' => -73.9940,
                'priority' => 'code_red',
                'nature_of_emergency' => 'Two-car high-speed motor vehicle collision with vehicle rollover. Extrication currently in progress.',
                'status' => 'at_scene',
                'patient_condition_notes' => 'Fire Department extricating driver. C-collar and backboard staged. Paramedic preparing rapid sequence airway.',
                'dispatched_by' => $user?->id,
                'dispatched_at' => $now->copy()->subMinutes(18),
                'en_route_scene_at' => $now->copy()->subMinutes(16),
                'arrived_scene_at' => $now->copy()->subMinutes(7),
            ]
        );

        // 4. Seed Emergency Cases with Automatic ESI Severity Queue
        // Case 1: ESI 1 (Resuscitation) - Linked to DISP-2026-0001
        $case1 = EmergencyCase::updateOrCreate(
            ['case_number' => 'ER-2026-0001'],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'patient_id' => $patient?->id,
                'arrival_mode' => 'ambulance',
                'ambulance_dispatch_id' => $disp1->id,
                'arrival_datetime' => $now->copy()->subMinutes(5),
                'chief_complaint' => 'Acute Crushing Retrosternal Chest Pain with ST Elevation (STEMI Alert)',
                'initial_triage_esi' => 1,
                'current_esi_level' => 1,
                'priority_score' => 100, // Highest Priority in Queue!
                'status' => 'bed_assigned',
                'assigned_doctor_id' => $user?->id,
                'assigned_nurse_id' => $user?->id,
                'assigned_bed_id' => $resusBed->id,
                'bed_assigned_at' => $now->copy()->subMinutes(3),
            ]
        );

        $disp1->update(['emergency_case_id' => $case1->id]);

        // Triage Record for Case 1
        TriageRecord::updateOrCreate(
            ['emergency_case_id' => $case1->id, 'esi_level' => 1],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'triaged_by' => $user?->id,
                'triaged_at' => $now->copy()->subMinutes(4),
                'severity_label' => 'Resuscitation (Immediate Life Threat)',
                'triage_category' => 'cardiac',
                'vital_signs' => [
                    'heart_rate' => 134,
                    'bp_systolic' => 84,
                    'bp_diastolic' => 52,
                    'respiratory_rate' => 28,
                    'spo2' => 89,
                    'temperature' => 36.8,
                    'gcs' => 14,
                    'pain_score' => 10,
                    'blood_glucose' => 145,
                ],
                'is_danger_zone_vitals' => true,
                'red_flags' => ['stemi_suspected', 'severe_hypoxia', 'hypotension_shock'],
                'assessment_notes' => 'Patient in cardiogenic distress. Marked ST-elevation in II, III, aVF. Cardiac catheterization team mobilized for immediate PCI.',
                'reassessment_interval_minutes' => 0,
            ]
        );

        // Emergency Bed Override Allocation for Case 1
        EmergencyBedAllocation::updateOrCreate(
            ['emergency_case_id' => $case1->id, 'bed_id' => $resusBed->id],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'is_override' => true,
                'override_reason' => 'Immediate acute STEMI resuscitation protocol; hemodynamic instability requiring high-acuity life support monitor and direct Cath Lab transfer.',
                'priority_tier' => 'ESI-1 Resuscitation Immediate Override',
                'allocated_by' => $user?->id,
                'allocated_at' => $now->copy()->subMinutes(3),
            ]
        );

        $resusBed->update(['status' => 'occupied']);

        // Case 2: ESI 2 (Emergent) - Unidentified Trauma Walk-in/Police
        $case2 = EmergencyCase::updateOrCreate(
            ['case_number' => 'ER-2026-0002'],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'patient_id' => null,
                'patient_temp_name' => 'Trauma John Doe (Approx 35yo Male)',
                'patient_gender' => 'male',
                'patient_estimated_age' => 35,
                'arrival_mode' => 'police',
                'arrival_datetime' => $now->copy()->subMinutes(20),
                'chief_complaint' => 'Blunt abdominal trauma, large scalp laceration with active bleeding, altered orientation following pedestrian vs auto collision.',
                'initial_triage_esi' => 2,
                'current_esi_level' => 2,
                'priority_score' => 80,
                'status' => 'in_treatment',
                'assigned_doctor_id' => $user?->id,
                'assigned_bed_id' => $traumaBed->id,
                'bed_assigned_at' => $now->copy()->subMinutes(15),
            ]
        );

        TriageRecord::updateOrCreate(
            ['emergency_case_id' => $case2->id, 'esi_level' => 2],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'triaged_by' => $user?->id,
                'triaged_at' => $now->copy()->subMinutes(18),
                'severity_label' => 'Emergent (High Risk / Danger Vitals)',
                'triage_category' => 'trauma',
                'vital_signs' => [
                    'heart_rate' => 118,
                    'bp_systolic' => 98,
                    'bp_diastolic' => 64,
                    'respiratory_rate' => 22,
                    'spo2' => 95,
                    'temperature' => 37.1,
                    'gcs' => 12,
                    'pain_score' => 8,
                ],
                'is_danger_zone_vitals' => true,
                'red_flags' => ['active_hemorrhage', 'altered_mental_status'],
                'assessment_notes' => 'Lethargic but responsive to verbal stimuli. FAST exam ordered immediately to rule out hemoperitoneum.',
                'reassessment_interval_minutes' => 15,
                'reassessment_due_at' => $now->copy()->addMinutes(15),
            ]
        );

        EmergencyBedAllocation::updateOrCreate(
            ['emergency_case_id' => $case2->id, 'bed_id' => $traumaBed->id],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'is_override' => false,
                'priority_tier' => 'ESI-2 Emergent High Priority',
                'allocated_by' => $user?->id,
                'allocated_at' => $now->copy()->subMinutes(15),
            ]
        );

        $traumaBed->update(['status' => 'occupied']);

        // Case 3: ESI 3 (Urgent) - Waiting in triage queue for standard bed
        $case3 = EmergencyCase::updateOrCreate(
            ['case_number' => 'ER-2026-0003'],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'patient_id' => $patient?->id,
                'arrival_mode' => 'walk_in',
                'arrival_datetime' => $now->copy()->subMinutes(40),
                'chief_complaint' => 'Acute lower right quadrant abdominal pain with persistent nausea, fever 38.6C. Suspected acute appendicitis.',
                'initial_triage_esi' => 3,
                'current_esi_level' => 3,
                'priority_score' => 60,
                'status' => 'triaged',
            ]
        );

        TriageRecord::updateOrCreate(
            ['emergency_case_id' => $case3->id, 'esi_level' => 3],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'triaged_by' => $user?->id,
                'triaged_at' => $now->copy()->subMinutes(35),
                'severity_label' => 'Urgent (Multi-Resource Stable)',
                'triage_category' => 'general',
                'vital_signs' => [
                    'heart_rate' => 88,
                    'bp_systolic' => 122,
                    'bp_diastolic' => 76,
                    'respiratory_rate' => 18,
                    'spo2' => 98,
                    'temperature' => 38.6,
                    'gcs' => 15,
                    'pain_score' => 7,
                ],
                'is_danger_zone_vitals' => false,
                'red_flags' => [],
                'assessment_notes' => 'Rebound tenderness at McBurney point. Labs and abdominal ultrasound ordered.',
                'reassessment_interval_minutes' => 30,
                'reassessment_due_at' => $now->copy()->addMinutes(25),
            ]
        );

        // Case 4: ESI 4 (Less Urgent) - Waiting in triage queue
        EmergencyCase::updateOrCreate(
            ['case_number' => 'ER-2026-0004'],
            [
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
                'patient_temp_name' => 'Maria Gonzalez',
                'patient_gender' => 'female',
                'patient_estimated_age' => 28,
                'arrival_mode' => 'walk_in',
                'arrival_datetime' => $now->copy()->subMinutes(55),
                'chief_complaint' => 'Deep knife laceration to left index and middle fingers while preparing food. Hemostasis maintained with pressure bandage.',
                'initial_triage_esi' => 4,
                'current_esi_level' => 4,
                'priority_score' => 40,
                'status' => 'triaged',
            ]
        );
    }
}
