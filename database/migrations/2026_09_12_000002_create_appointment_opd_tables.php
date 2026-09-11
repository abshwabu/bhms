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
        // 1. Departments Table
        Schema::create('departments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 20); // GEN, PED, CARD, ORTH, GYNAE
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'code']);
            $table->index('branch_id');
        });

        // 2. Doctor Schedules Table (Recurring + Specific Date Overrides)
        Schema::create('doctor_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignUuid('doctor_id')->constrained('users')->cascadeOnDelete();

            $table->string('schedule_type', 20)->default('recurring'); // recurring, specific_date
            $table->smallInteger('day_of_week')->nullable(); // 0 = Sunday, 1 = Monday ... 6 = Saturday
            $table->date('specific_date')->nullable();
            
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('slot_duration_minutes')->default(15);
            $table->integer('max_patients')->nullable();
            $table->boolean('is_available')->default(true); // false = leave / unavailable
            $table->string('room_number', 50)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['doctor_id', 'day_of_week']);
            $table->index(['doctor_id', 'specific_date']);
            $table->index('branch_id');
        });

        // 3. Appointments Table
        Schema::create('appointments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignUuid('doctor_id')->constrained('users')->restrictOnDelete();
            $table->uuid('parent_appointment_id')->nullable(); // Self reference added below

            $table->string('appointment_number', 50)->unique();
            $table->date('appointment_date');
            $table->time('start_time');
            $table->time('end_time');
            
            $table->string('type', 30)->default('in_person'); // in_person, telemedicine, walk_in, follow_up
            $table->string('status', 30)->default('scheduled'); // scheduled, checked_in, in_consultation, completed, cancelled, no_show, rescheduled
            $table->text('reason_for_visit')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignUuid('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_in_at')->nullable();
            $table->foreignUuid('booked_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['doctor_id', 'appointment_date']);
            $table->index(['patient_id', 'appointment_date']);
            $table->index(['branch_id', 'appointment_date', 'status']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreign('parent_appointment_id')->references('id')->on('appointments')->nullOnDelete();
        });

        // PostgreSQL Partial Unique Index: PREVENTS DOUBLE-BOOKING AT DATABASE LEVEL
        DB::statement("
            CREATE UNIQUE INDEX idx_appointments_no_double_booking 
            ON appointments (doctor_id, appointment_date, start_time) 
            WHERE status NOT IN ('cancelled', 'rescheduled') AND deleted_at IS NULL;
        ");

        // 4. Queue Tokens Table (Resets per day per department)
        Schema::create('queue_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('department_id')->constrained('departments')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('doctor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();

            $table->date('token_date');
            $table->integer('token_number'); // Sequential: 1, 2, 3...
            $table->string('token_code', 30); // e.g. GEN-001, PED-014
            $table->string('status', 30)->default('waiting'); // waiting, called, in_consultation, completed, skipped, cancelled
            $table->string('priority', 20)->default('normal'); // normal, urgent, emergency
            $table->string('counter_room', 50)->nullable(); // e.g. 'Room 102'

            $table->timestamp('called_at')->nullable();
            $table->timestamp('consultation_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'department_id', 'token_date', 'token_number']);
            $table->index(['branch_id', 'token_date', 'status']);
            $table->index(['department_id', 'token_date', 'status']);
        });

        // 5. Consultation Notes (SOAP Format - Versioned & Immutable upon sign-off)
        Schema::create('consultation_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('doctor_id')->constrained('users')->restrictOnDelete();
            $table->uuid('parent_note_id')->nullable(); // Self reference added below
            $table->integer('version')->default(1);

            // Subjective (S)
            $table->text('chief_complaint');
            $table->text('history_of_presenting_illness')->nullable();
            $table->jsonb('review_of_systems')->nullable();

            // Objective (O)
            $table->jsonb('vitals')->default('{}'); // bp_systolic, bp_diastolic, heart_rate, temp_c, spo2, rr, weight_kg, height_cm
            $table->text('physical_examination')->nullable();

            // Assessment (A)
            $table->string('provisional_diagnosis', 255);
            $table->text('differential_diagnoses')->nullable();
            $table->jsonb('icd10_codes')->default('[]');

            // Plan (P)
            $table->text('treatment_plan');
            $table->text('prescriptions_advice')->nullable();
            $table->text('orders_requested')->nullable(); // Labs, Radiology
            $table->text('diet_and_lifestyle_advice')->nullable();
            $table->date('follow_up_recommended_date')->nullable();
            $table->text('follow_up_instructions')->nullable();

            // Sign-off & Immutability Ledger
            $table->boolean('is_signed_off')->default(false);
            $table->timestamp('signed_off_at')->nullable();
            $table->foreignUuid('signed_off_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes_status', 20)->default('draft'); // draft, signed_off, amended
            $table->text('amendment_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'created_at']);
            $table->index(['doctor_id', 'created_at']);
            $table->index('appointment_id');
        });

        Schema::table('consultation_notes', function (Blueprint $table) {
            $table->foreign('parent_note_id')->references('id')->on('consultation_notes')->nullOnDelete();
        });

        // 6. Referrals Table (Internal Departmental & External Facility)
        Schema::create('referrals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('referring_doctor_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('consultation_note_id')->nullable()->constrained('consultation_notes')->nullOnDelete();

            $table->string('referral_type', 30)->default('internal_department'); // internal_department, external_facility
            $table->foreignUuid('from_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignUuid('to_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignUuid('to_doctor_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('external_facility_name', 255)->nullable();
            $table->string('external_specialist_name', 255)->nullable();
            $table->string('external_contact', 100)->nullable();

            $table->string('priority', 20)->default('routine'); // routine, urgent, emergency
            $table->text('reason_for_referral');
            $table->text('clinical_summary')->nullable();
            $table->string('status', 30)->default('pending'); // pending, accepted, completed, rejected, cancelled

            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'status']);
            $table->index(['to_department_id', 'status']);
            $table->index(['to_doctor_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('consultation_notes');
        Schema::dropIfExists('queue_tokens');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('doctor_schedules');
        Schema::dropIfExists('departments');
    }
};
