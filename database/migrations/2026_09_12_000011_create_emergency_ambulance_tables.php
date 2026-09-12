<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Emergency & Ambulance module.
     */
    public function up(): void
    {
        // 1. Ambulances Fleet Table
        Schema::create('ambulances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();

            $table->string('vehicle_number', 50); // e.g. AMB-01, AMB-02
            $table->string('call_sign', 50); // e.g. Medic-1, Rescue-4
            $table->string('ambulance_type', 30)->default('als'); // bls, als, cct, neonatal
            $table->string('model', 100); // e.g. Ford Transit 350HD, Mercedes Sprinter
            $table->string('plate_number', 50);
            $table->string('status', 30)->default('available'); // available, dispatched, en_route_scene, at_scene, en_route_hospital, arrived_hospital, maintenance, off_duty

            // Medical Equipment Payload
            $table->jsonb('equipment')->default('{}'); // ventilator, defibrillator, cardiac_monitor, suction, trauma_kit

            // Live Telemetry / GPS Coordinates
            $table->decimal('current_latitude', 10, 7)->nullable();
            $table->decimal('current_longitude', 10, 7)->nullable();
            $table->decimal('heading', 5, 2)->nullable(); // 0-360 degrees
            $table->decimal('speed_kmh', 5, 2)->default(0);
            $table->integer('fuel_percentage')->default(100);
            $table->timestamp('last_telemetry_at')->nullable();

            // Crew Assignment
            $table->string('assigned_driver_name', 100)->nullable();
            $table->string('assigned_paramedic_name', 100)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'vehicle_number']);
            $table->index(['branch_id', 'status']);
        });

        // 2. Emergency Cases (Triage & Queue Master)
        Schema::create('emergency_cases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();

            $table->string('case_number', 50)->unique(); // e.g. ER-2026-0001
            $table->foreignUuid('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->string('patient_temp_name', 100)->nullable(); // e.g. Unknown Male ~40yo
            $table->string('patient_gender', 20)->nullable(); // male, female, other, unknown
            $table->integer('patient_estimated_age')->nullable();

            $table->string('arrival_mode', 30)->default('ambulance'); // ambulance, walk_in, helicopter, police
            $table->uuid('ambulance_dispatch_id')->nullable(); // Foreign key added after ambulance_dispatches is created
            $table->timestamp('arrival_datetime');
            $table->text('chief_complaint');

            // Automatic Priority Driven by ESI Level (1 to 5)
            $table->integer('initial_triage_esi')->default(3); // 1: Resuscitation, 2: Emergent, 3: Urgent, 4: Less Urgent, 5: Non-Urgent
            $table->integer('current_esi_level')->default(3);
            $table->integer('priority_score')->default(60); // ESI 1=100, ESI 2=80, ESI 3=60, ESI 4=40, ESI 5=20

            $table->string('status', 30)->default('triaged'); // registered, triaged, in_treatment, bed_assigned, admitted_ipd, discharged, transferred, deceased
            $table->foreignUuid('assigned_doctor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('assigned_nurse_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('assigned_bed_id')->nullable()->constrained('beds')->nullOnDelete();
            $table->timestamp('bed_assigned_at')->nullable();

            // Emergency Disposition
            $table->string('disposition', 50)->nullable(); // admit_ipd, discharge_home, transfer_tertiary, ama, morgue
            $table->text('disposition_notes')->nullable();
            $table->timestamp('disposition_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status']);
            $table->index(['branch_id', 'current_esi_level', 'priority_score']);
            $table->index(['arrival_datetime']);
        });

        // 3. Ambulance Dispatches
        Schema::create('ambulance_dispatches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('ambulance_id')->constrained('ambulances')->restrictOnDelete();
            $table->foreignUuid('emergency_case_id')->nullable()->constrained('emergency_cases')->nullOnDelete();

            $table->string('dispatch_number', 50)->unique(); // e.g. DISP-2026-0001
            $table->string('caller_name', 100)->nullable();
            $table->string('caller_phone', 50)->nullable();
            $table->text('pickup_address');
            $table->decimal('pickup_latitude', 10, 7)->nullable();
            $table->decimal('pickup_longitude', 10, 7)->nullable();

            $table->text('destination_address')->nullable();
            $table->decimal('destination_latitude', 10, 7)->nullable();
            $table->decimal('destination_longitude', 10, 7)->nullable();

            $table->string('priority', 30)->default('code_red'); // code_red (life-threatening), code_yellow (urgent), code_green (routine)
            $table->text('nature_of_emergency');
            $table->string('status', 30)->default('dispatched'); // dispatched, en_route_scene, at_scene, en_route_hospital, arrived_hospital, completed, cancelled

            $table->text('patient_condition_notes')->nullable();
            $table->foreignUuid('dispatched_by')->nullable()->constrained('users')->nullOnDelete();

            // Lifecycle Timestamps
            $table->timestamp('dispatched_at');
            $table->timestamp('en_route_scene_at')->nullable();
            $table->timestamp('arrived_scene_at')->nullable();
            $table->timestamp('departed_scene_at')->nullable();
            $table->timestamp('arrived_hospital_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 255)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status']);
            $table->index(['ambulance_id', 'status']);
        });

        // Add foreign key constraint from emergency_cases to ambulance_dispatches
        Schema::table('emergency_cases', function (Blueprint $table) {
            $table->foreign('ambulance_dispatch_id')
                ->references('id')
                ->on('ambulance_dispatches')
                ->nullOnDelete();
        });

        // 4. Triage Records (Severity Classification, Danger Zone Vitals & Red Flags)
        Schema::create('triage_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('emergency_case_id')->constrained('emergency_cases')->cascadeOnDelete();
            $table->foreignUuid('triaged_by')->constrained('users')->restrictOnDelete();

            $table->timestamp('triaged_at');
            $table->integer('esi_level'); // 1: Resuscitation, 2: Emergent, 3: Urgent, 4: Less Urgent, 5: Non-Urgent
            $table->string('severity_label', 100);
            $table->string('triage_category', 50)->default('general'); // resuscitation, cardiac, trauma, respiratory, neurological, pediatric, burns, general

            // Quantitative Vitals (JSONB)
            $table->jsonb('vital_signs')->default('{}'); // heart_rate, bp_systolic, bp_diastolic, respiratory_rate, spo2, temperature, gcs, pain_score, blood_glucose
            $table->boolean('is_danger_zone_vitals')->default(false);
            $table->jsonb('red_flags')->default('[]'); // severe_hypoxia, altered_mental_status, stemi_suspected, active_hemorrhage, etc.

            $table->text('assessment_notes');
            $table->integer('reassessment_interval_minutes')->default(30);
            $table->timestamp('reassessment_due_at')->nullable();

            $table->timestamps();

            $table->index(['emergency_case_id', 'esi_level']);
            $table->index(['branch_id', 'esi_level']);
        });

        // 5. Emergency Bed Allocations (With Priority Override & Logged Justification)
        Schema::create('emergency_bed_allocations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('emergency_case_id')->constrained('emergency_cases')->cascadeOnDelete();
            $table->foreignUuid('bed_id')->constrained('beds')->restrictOnDelete();

            $table->boolean('is_override')->default(false); // Acceptance criterion: Emergency bed allocation can override standard bed queue
            $table->text('override_reason')->nullable(); // Mandatory justification if is_override is true
            $table->string('priority_tier', 100); // e.g. ESI-1 Resuscitation Override, ESI-2 Emergent Priority, Standard Emergency Bed

            $table->foreignUuid('allocated_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('allocated_at');
            $table->timestamp('released_at')->nullable();
            $table->text('release_notes')->nullable();

            $table->timestamps();

            $table->index(['emergency_case_id', 'bed_id']);
            $table->index(['branch_id', 'is_override']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emergency_cases', function (Blueprint $table) {
            $table->dropForeign(['ambulance_dispatch_id']);
        });

        Schema::dropIfExists('emergency_bed_allocations');
        Schema::dropIfExists('triage_records');
        Schema::dropIfExists('ambulance_dispatches');
        Schema::dropIfExists('emergency_cases');
        Schema::dropIfExists('ambulances');
    }
};
