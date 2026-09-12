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
        // 1. Standard ICD-10 Master Dictionary Table
        Schema::create('icd10_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 20)->unique();
            $table->string('description', 500);
            $table->string('category', 255)->nullable();
            $table->string('chapter', 20)->nullable();
            $table->boolean('is_billable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('category');
            $table->index('is_active');
        });

        // 2. Electronic Health Records (EHR) Table (Append-only / Versioned)
        Schema::create('ehr_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->restrictOnDelete();
            
            $table->string('encounter_type', 30)->default('opd_appointment'); // opd_appointment, ipd_admission, emergency, walk_in, direct_entry
            $table->uuid('encounter_id')->nullable(); // appointment_id or admission_id
            $table->foreignUuid('author_id')->constrained('users')->restrictOnDelete();
            $table->string('record_type', 50)->default('consultation_note'); // consultation_note, progress_note, soap_note, admission_note, discharge_summary, emergency_note
            $table->string('category', 50)->default('general');
            $table->string('title', 255);
            
            // Clinical Content (SOAP format or structured clinical narrative)
            $table->jsonb('clinical_notes')->default('{}'); // chief_complaint, subjective, objective, assessment, plan, remarks
            $table->jsonb('vitals')->default('{}'); // bp_systolic, bp_diastolic, heart_rate, respiratory_rate, temp_c, spo2, bmi
            
            // Append-only & Versioning controls
            $table->string('status', 30)->default('draft'); // draft, finalized, amended
            $table->integer('version')->default(1);
            $table->boolean('is_amended')->default(false);
            $table->uuid('amended_from_id')->nullable();
            $table->text('amendment_reason')->nullable();
            
            $table->timestamp('finalized_at')->nullable();
            $table->foreignUuid('finalized_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'created_at']);
            $table->index(['branch_id', 'status']);
            $table->index(['author_id', 'created_at']);
            $table->index('encounter_id');
            $table->index('version');
        });

        Schema::table('ehr_records', function (Blueprint $table) {
            $table->foreign('amended_from_id')->references('id')->on('ehr_records')->nullOnDelete();
        });

        // 3. Clinical Diagnoses Table (ICD-10 Coded)
        Schema::create('diagnoses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->restrictOnDelete();
            
            $table->foreignUuid('ehr_record_id')->nullable()->constrained('ehr_records')->nullOnDelete();
            $table->foreignUuid('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignUuid('admission_id')->nullable()->constrained('admissions')->nullOnDelete();
            $table->foreignUuid('doctor_id')->constrained('users')->restrictOnDelete();

            $table->string('icd10_code', 20);
            $table->string('icd10_title', 255);
            $table->string('type', 30)->default('primary'); // primary, secondary, differential, provisional, working
            $table->string('severity', 30)->default('moderate'); // mild, moderate, severe
            $table->string('clinical_status', 30)->default('active'); // active, recurrence, remission, resolved
            $table->string('verification_status', 30)->default('confirmed'); // confirmed, provisional, differential, refuted
            
            $table->date('onset_date')->nullable();
            $table->date('resolved_date')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'clinical_status']);
            $table->index('icd10_code');
            $table->index(['doctor_id', 'created_at']);
            $table->index('ehr_record_id');
        });

        // 4. Prescriptions Table
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('prescription_number', 50)->unique();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->restrictOnDelete();

            $table->foreignUuid('ehr_record_id')->nullable()->constrained('ehr_records')->nullOnDelete();
            $table->foreignUuid('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignUuid('admission_id')->nullable()->constrained('admissions')->nullOnDelete();
            $table->foreignUuid('doctor_id')->constrained('users')->restrictOnDelete();

            $table->string('status', 30)->default('draft'); // draft, pending_override, finalized, cancelled, dispensed
            $table->boolean('has_safety_warnings')->default(false);
            $table->jsonb('safety_alerts')->default('[]'); // Drug-drug interactions, drug-allergy alerts
            
            // Clinical decision support override audit trail
            $table->text('override_reason')->nullable();
            $table->foreignUuid('overridden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('overridden_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamp('prescribed_at');
            $table->timestamp('finalized_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'status']);
            $table->index(['doctor_id', 'prescribed_at']);
            $table->index(['branch_id', 'prescribed_at']);
            $table->index('prescription_number');
        });

        // 5. Prescription Items Table (Detailed Dosing, Frequency, Duration)
        Schema::create('prescription_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('prescription_id')->constrained('prescriptions')->cascadeOnDelete();

            $table->string('medication_name', 255);
            $table->string('generic_name', 255)->nullable();
            $table->string('form', 50)->default('tablet'); // tablet, capsule, syrup, injection, inhaler, ointment, drops
            $table->string('dosage', 100); // e.g. "500 mg", "10 ml"
            $table->string('route', 50)->default('oral'); // oral, intravenous, intramuscular, sublingual, topical, inhalation
            $table->string('frequency', 100); // e.g. "TID (Three times daily)", "BID", "OD", "PRN"
            $table->integer('duration_days')->default(7);
            $table->integer('quantity')->default(1);
            $table->text('instructions')->nullable();
            $table->boolean('is_substitution_allowed')->default(true);
            $table->string('status', 30)->default('pending'); // pending, dispensed, cancelled

            $table->timestamps();
            $table->softDeletes();

            $table->index('prescription_id');
            $table->index('medication_name');
            $table->index('generic_name');
        });

        // 6. Laboratory Orders Table
        Schema::create('lab_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('order_number', 50)->unique();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->restrictOnDelete();

            $table->foreignUuid('ehr_record_id')->nullable()->constrained('ehr_records')->nullOnDelete();
            $table->foreignUuid('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignUuid('admission_id')->nullable()->constrained('admissions')->nullOnDelete();
            $table->foreignUuid('ordering_doctor_id')->constrained('users')->restrictOnDelete();

            $table->string('test_type', 150); // e.g. "Complete Blood Count (CBC)", "HbA1c", "Lipid Panel"
            $table->string('test_code', 50)->nullable();
            $table->string('priority', 30)->default('routine'); // routine, urgent, stat
            $table->text('clinical_indication')->nullable();
            $table->text('special_instructions')->nullable();
            
            $table->string('status', 30)->default('ordered'); // ordered, sample_collected, in_progress, completed, cancelled
            $table->timestamp('ordered_at');
            $table->timestamp('sample_collected_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->text('results_summary')->nullable();
            $table->jsonb('structured_results')->default('[]');
            $table->boolean('abnormal_flags')->default(false);
            
            // Doctor Review and Sign-Off
            $table->foreignUuid('reviewed_by_doctor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_by_doctor_at')->nullable();
            $table->text('doctor_review_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'status']);
            $table->index(['ordering_doctor_id', 'ordered_at']);
            $table->index(['branch_id', 'status']);
            $table->index('order_number');
            $table->index('reviewed_by_doctor_at');
        });

        // 7. Radiology Orders Table
        Schema::create('radiology_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('order_number', 50)->unique();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->restrictOnDelete();

            $table->foreignUuid('ehr_record_id')->nullable()->constrained('ehr_records')->nullOnDelete();
            $table->foreignUuid('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignUuid('admission_id')->nullable()->constrained('admissions')->nullOnDelete();
            $table->foreignUuid('ordering_doctor_id')->constrained('users')->restrictOnDelete();

            $table->string('modality', 50); // X-Ray, CT Scan, MRI, Ultrasound, Mammography
            $table->string('body_part', 100); // Chest, Brain, Lumbar Spine, Abdomen, Knee
            $table->string('procedure_name', 255);
            $table->string('priority', 30)->default('routine'); // routine, urgent, stat
            $table->text('clinical_indication')->nullable();
            $table->boolean('transport_required')->default(false);
            $table->boolean('is_pregnant_or_possible')->default(false);

            $table->string('status', 30)->default('ordered'); // ordered, scheduled, performed, reported, cancelled
            $table->timestamp('ordered_at');
            $table->timestamp('performed_at')->nullable();
            $table->timestamp('reported_at')->nullable();

            $table->text('findings')->nullable();
            $table->text('impression')->nullable();
            $table->foreignUuid('radiologist_id')->nullable()->constrained('users')->nullOnDelete();

            // Doctor Review and Sign-Off
            $table->foreignUuid('reviewed_by_doctor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_by_doctor_at')->nullable();
            $table->text('doctor_review_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'status']);
            $table->index(['ordering_doctor_id', 'ordered_at']);
            $table->index(['branch_id', 'status']);
            $table->index('order_number');
            $table->index('reviewed_by_doctor_at');
        });

        // Add PostgreSQL Trigram Index for fast ICD-10 Search if pgsql
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("CREATE INDEX IF NOT EXISTS idx_icd10_trgm_search ON icd10_codes USING gin ((code || ' ' || description) gin_trgm_ops);");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('radiology_orders');
        Schema::dropIfExists('lab_orders');
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('diagnoses');
        Schema::dropIfExists('ehr_records');
        Schema::dropIfExists('icd10_codes');
    }
};
