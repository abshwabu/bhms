<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Radiology Information System (RIS).
     */
    public function up(): void
    {
        // 1. Imaging Orders Table (Intake, Modality, Scheduling & Worklist)
        Schema::create('imaging_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('accession_number', 50)->unique(); // e.g. ACC-2026-XXXXXXXX
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->restrictOnDelete();
            
            // Link to Clinical Order from Doctor consultation
            $table->foreignUuid('radiology_order_id')->nullable()->constrained('radiology_orders')->nullOnDelete();
            $table->foreignUuid('ordering_doctor_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('technologist_id')->nullable()->constrained('users')->nullOnDelete();

            // Modality and Clinical Details
            $table->string('modality', 50); // X-Ray, CT, MRI, Ultrasound, Mammography, PET-CT, Fluoroscopy
            $table->string('procedure_code', 50)->nullable(); // e.g. CPT-71046, RAD-XR-01
            $table->string('procedure_name', 255);
            $table->string('body_part', 100); // Chest, Brain, Abdomen, Pelvis, Lumbar Spine, Knee, etc.
            $table->string('priority', 30)->default('routine'); // routine, urgent, stat
            $table->text('clinical_indication')->nullable();
            $table->text('patient_preparation')->nullable(); // NPO for 6 hours, IV contrast prep, etc.
            $table->boolean('is_pregnant_or_possible')->default(false);
            $table->string('transport_mode', 50)->default('ambulatory'); // ambulatory, wheelchair, stretcher, portable_bedside

            // Scheduling and Workflow
            $table->string('status', 30)->default('ordered'); // ordered, scheduled, in_progress, completed, cancelled
            $table->timestamp('scheduled_at')->nullable();
            $table->string('scheduled_room', 100)->nullable(); // e.g. "CT Room 1", "MRI 3T Suite", "X-Ray Bay 2"
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // PACS / DICOM Study linkage
            $table->string('dicom_study_uid', 128)->nullable(); // DICOM StudyInstanceUID
            $table->string('pacs_status', 50)->default('pending'); // pending, available, archived
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('accession_number');
            $table->index(['patient_id', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index(['modality', 'status']);
            $table->index('scheduled_at');
            $table->index('radiology_order_id');
        });

        // 2. Imaging Reports Table (Radiologist Report Entry, Draft, Finalize, Critical Alerts)
        Schema::create('imaging_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('report_number', 50)->unique(); // e.g. RAD-REP-2026-XXXXXX
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('imaging_order_id')->constrained('imaging_orders')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignUuid('radiologist_id')->constrained('users')->restrictOnDelete();

            $table->string('status', 30)->default('draft'); // draft, preliminary, finalized, amended
            $table->text('clinical_indication')->nullable();
            $table->text('technique')->nullable(); // Scanning technique, parameters, contrast type & dose
            $table->text('comparison')->nullable(); // Previous studies compared against
            $table->text('findings')->nullable(); // Detailed anatomical breakdown of findings
            $table->text('impression'); // Core diagnosis / summary / BI-RADS / Lung-RADS score
            $table->text('recommendations')->nullable(); // Recommended follow-up, biopsy, or correlation

            // Critical Findings Panic Alert
            $table->boolean('critical_alert')->default(false); // Acute intracranial hemorrhage, aortic dissection, pneumothorax
            $table->timestamp('critical_alert_communicated_at')->nullable();
            $table->string('critical_alert_communicated_to', 255)->nullable(); // Clinician name & communication channel

            // Digital Signature and Finalization
            $table->string('digital_signature_hash', 255)->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->foreignUuid('finalized_by')->nullable()->constrained('users')->nullOnDelete();

            // Append-Only Amendments & Versioning
            $table->integer('version')->default(1);
            $table->boolean('is_amended')->default(false);
            $table->uuid('amended_from_id')->nullable();
            $table->text('amendment_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('report_number');
            $table->index(['imaging_order_id', 'status']);
            $table->index(['patient_id', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index('critical_alert');
            $table->index('finalized_at');
        });

        // Separate foreign key for self-referencing amendment hierarchy
        Schema::table('imaging_reports', function (Blueprint $table) {
            $table->foreign('amended_from_id')->references('id')->on('imaging_reports')->nullOnDelete();
        });

        // 3. Imaging Files Table (DICOM, JPEG, PNG, PACS integration links)
        Schema::create('imaging_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('imaging_order_id')->constrained('imaging_orders')->cascadeOnDelete();
            $table->foreignUuid('imaging_report_id')->nullable()->constrained('imaging_reports')->nullOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->restrictOnDelete();

            $table->string('file_name', 255);
            $table->string('original_file_name', 255);
            $table->string('file_path', 500);
            $table->string('disk', 50)->default('public'); // public, local, s3
            $table->string('mime_type', 100); // application/dicom, image/jpeg, image/png, etc.
            $table->bigInteger('file_size_bytes')->default(0);

            // DICOM metadata
            $table->boolean('is_dicom')->default(false);
            $table->string('dicom_sop_instance_uid', 128)->nullable();
            $table->string('series_description', 150)->nullable();
            $table->integer('series_number')->nullable();
            $table->integer('instance_number')->nullable();
            $table->decimal('window_center', 10, 2)->nullable();
            $table->decimal('window_width', 10, 2)->nullable();

            // External PACS Integration URLs (Orthanc, dcm4chee, OHIF / WADO-RS viewer)
            $table->string('pacs_wado_url', 500)->nullable();
            $table->string('pacs_preview_url', 500)->nullable();
            $table->string('thumbnail_path', 500)->nullable();
            $table->jsonb('metadata')->nullable();

            $table->string('upload_status', 30)->default('completed'); // pending, uploading, completed, failed
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['imaging_order_id', 'is_dicom']);
            $table->index(['patient_id', 'created_at']);
            $table->index('is_dicom');
        });

        // 4. Imaging File Chunks Table (Chunked upload for large multi-hundred-megabyte DICOM / high-res files)
        Schema::create('imaging_file_chunks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('upload_id', 64)->index();
            $table->integer('chunk_index');
            $table->integer('total_chunks');
            $table->string('chunk_file_path', 500);
            $table->bigInteger('chunk_size_bytes');
            $table->boolean('is_assembled')->default(false);
            $table->timestamps();

            $table->unique(['upload_id', 'chunk_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imaging_file_chunks');
        Schema::dropIfExists('imaging_files');
        Schema::dropIfExists('imaging_reports');
        Schema::dropIfExists('imaging_orders');
    }
};
