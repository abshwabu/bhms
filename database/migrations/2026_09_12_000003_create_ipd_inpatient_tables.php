<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Wards Table
        Schema::create('wards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('department_id')->nullable()->constrained('departments')->nullOnDelete();

            $table->string('name', 150);
            $table->string('code', 20); // e.g. MMW, FSW, ICU, PED-IN
            $table->string('ward_type', 50)->default('general'); // general, semi_private, private, icu, hcu, isolation, maternity, pediatric
            $table->string('floor_number', 20)->nullable();
            $table->integer('capacity')->default(10);
            $table->string('gender_restriction', 20)->default('all'); // all, male_only, female_only
            $table->decimal('daily_rate', 10, 2)->default(0.00);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'code']);
            $table->index(['branch_id', 'is_active']);
        });

        // 2. Beds Table
        Schema::create('beds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('ward_id')->constrained('wards')->cascadeOnDelete();

            $table->string('bed_number', 30); // e.g. B-101, ICU-01
            $table->string('bed_type', 50)->default('standard'); // standard, electric, icu_ventilator, crib, bariatric
            $table->string('status', 30)->default('available'); // available, occupied, cleaning, maintenance, reserved
            $table->jsonb('features')->default('{}'); // oxygen, suction, ventilator, monitor
            $table->decimal('daily_rate_override', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['ward_id', 'bed_number']);
            $table->index(['branch_id', 'status']);
            $table->index(['ward_id', 'status']);
        });

        // 3. Inpatient Admissions Table (ADT Workflow)
        Schema::create('admissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('admission_number', 50)->unique(); // e.g. ADM-2026-MAIN-000001
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignUuid('ward_id')->constrained('wards')->restrictOnDelete();
            $table->foreignUuid('bed_id')->constrained('beds')->restrictOnDelete();
            $table->foreignUuid('admitting_doctor_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('attending_doctor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('admitted_by')->constrained('users')->restrictOnDelete(); // Staff ID who performed admission

            $table->string('admission_type', 30)->default('emergency'); // emergency, elective, transfer, observation, maternity
            $table->string('status', 30)->default('admitted'); // admitted, discharged, transferred_out, cancelled
            $table->timestamp('admitted_at');
            $table->timestamp('discharged_at')->nullable();
            $table->foreignUuid('discharged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('discharge_type', 30)->nullable(); // routine, ama, transfer, deceased

            $table->text('admitting_diagnosis');
            $table->text('primary_diagnosis')->nullable();
            $table->jsonb('secondary_diagnoses')->default('[]');
            $table->jsonb('procedures_performed')->default('[]');
            $table->text('chief_complaint')->nullable();
            $table->jsonb('initial_vitals')->default('{}');
            $table->foreignUuid('insurance_policy_id')->nullable()->constrained('patient_insurance')->nullOnDelete();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status']);
            $table->index(['patient_id', 'status']);
            $table->index(['bed_id', 'status']);
        });

        // PostgreSQL Partial Unique Index: A bed cannot be assigned to two active admissions simultaneously!
        DB::statement("
            CREATE UNIQUE INDEX idx_admissions_no_double_bed_assignment 
            ON admissions (bed_id) 
            WHERE status = 'admitted' AND deleted_at IS NULL;
        ");

        // 4. Bed Transfers Audit Ledger
        Schema::create('bed_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('admission_id')->constrained('admissions')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();

            $table->foreignUuid('from_ward_id')->constrained('wards')->restrictOnDelete();
            $table->foreignUuid('from_bed_id')->constrained('beds')->restrictOnDelete();
            $table->foreignUuid('to_ward_id')->constrained('wards')->restrictOnDelete();
            $table->foreignUuid('to_bed_id')->constrained('beds')->restrictOnDelete();

            $table->text('reason');
            $table->foreignUuid('transferred_by')->constrained('users')->restrictOnDelete(); // Staff ID
            $table->timestamp('transferred_at');
            $table->string('status', 30)->default('completed');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['admission_id', 'transferred_at']);
            $table->index('patient_id');
        });

        // 5. Nursing Station Vitals Logs
        Schema::create('vitals_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('admission_id')->constrained('admissions')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('recorded_by')->constrained('users')->restrictOnDelete(); // Nurse / Staff ID

            $table->timestamp('recorded_at');
            $table->integer('bp_systolic')->nullable();
            $table->integer('bp_diastolic')->nullable();
            $table->integer('heart_rate')->nullable();
            $table->integer('respiratory_rate')->nullable();
            $table->decimal('temperature_c', 4, 1)->nullable();
            $table->decimal('spo2', 4, 1)->nullable();
            $table->decimal('blood_glucose_mg_dl', 5, 1)->nullable();
            $table->integer('pain_score')->nullable(); // 0-10
            $table->string('consciousness_level', 20)->nullable(); // alert, voice, pain, unresponsive
            $table->decimal('urine_output_ml', 6, 1)->nullable();
            $table->text('nursing_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['admission_id', 'recorded_at']);
            $table->index(['patient_id', 'recorded_at']);
        });

        // 6. Medication Administration Records (Nursing Rounds)
        Schema::create('medication_administrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('admission_id')->constrained('admissions')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('administered_by')->constrained('users')->restrictOnDelete(); // Nurse/Staff ID

            $table->string('medication_name', 255);
            $table->string('dosage', 100);
            $table->string('route', 50); // oral, iv, im, sc, inhalation, topical
            $table->timestamp('scheduled_time')->nullable();
            $table->timestamp('administered_at');
            $table->string('status', 30)->default('given'); // given, missed, refused, held
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['admission_id', 'administered_at']);
            $table->index(['patient_id', 'administered_at']);
        });

        // 7. Discharge Summaries
        Schema::create('discharge_summaries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('admission_id')->unique()->constrained('admissions')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('discharging_doctor_id')->constrained('users')->restrictOnDelete();

            // Auto-populated stay records
            $table->timestamp('admission_date');
            $table->timestamp('discharge_date');
            $table->text('primary_diagnosis');
            $table->jsonb('secondary_diagnoses')->default('[]');
            $table->jsonb('procedures_performed')->default('[]');
            $table->jsonb('medications_at_discharge')->default('[]');

            $table->text('hospital_course_summary');
            $table->string('discharge_condition', 50)->default('stable'); // cured, improved, stable, transferred, ama, deceased
            $table->string('discharge_type', 50)->default('regular'); // regular, ama, transfer, deceased
            $table->text('follow_up_instructions')->nullable();
            $table->date('follow_up_date')->nullable();

            $table->boolean('is_finalized')->default(false);
            $table->timestamp('finalized_at')->nullable();
            $table->foreignUuid('finalized_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('admission_id');
            $table->index('patient_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discharge_summaries');
        Schema::dropIfExists('medication_administrations');
        Schema::dropIfExists('vitals_logs');
        Schema::dropIfExists('bed_transfers');
        Schema::dropIfExists('admissions');
        Schema::dropIfExists('beds');
        Schema::dropIfExists('wards');
    }
};
