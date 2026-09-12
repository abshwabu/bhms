<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Lab Tests Catalog Master Table
        Schema::create('lab_tests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique(); // e.g. CBC, BMP, CMP, LFT, LIPID, HBA1C, TSH
            $table->string('name', 150);
            $table->string('category', 100); // Hematology, Clinical Chemistry, Immunology, Microbiology, Urinalysis
            $table->string('specimen_type', 50)->default('Whole Blood'); // Whole Blood, Serum, Plasma, Urine, Stool, CSF
            $table->string('container_type', 100)->default('EDTA (Purple Top)'); // EDTA, SST Gel, Citrate, Sterile Cup
            $table->integer('turn_around_time_minutes')->default(60);
            $table->integer('price_cents')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('category');
            $table->index('is_active');
        });

        // 2. Reference Ranges Table (Parameter-level normal & panic limits)
        Schema::create('reference_ranges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lab_test_id')->constrained('lab_tests')->cascadeOnDelete();
            
            $table->string('parameter_name', 100); // e.g. Hemoglobin, WBC, Platelets, Glucose, Creatinine
            $table->string('unit', 30)->nullable(); // g/dL, 10^3/uL, mg/dL, mmol/L
            $table->string('gender', 20)->default('all'); // all, male, female
            $table->integer('age_min_years')->nullable()->default(0);
            $table->integer('age_max_years')->nullable()->default(120);
            
            $table->decimal('normal_low', 10, 3)->nullable();
            $table->decimal('normal_high', 10, 3)->nullable();
            $table->decimal('critical_low', 10, 3)->nullable(); // Panic low
            $table->decimal('critical_high', 10, 3)->nullable(); // Panic high
            $table->string('qualitative_normal', 100)->nullable(); // Negative, Non-reactive
            
            $table->timestamps();
            $table->softDeletes();

            $table->index(['lab_test_id', 'parameter_name']);
            $table->index('gender');
        });

        // 3. Lab Samples Table (Specimen tube tracking with barcodes)
        Schema::create('lab_samples', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('barcode', 50)->unique(); // SMP-YYYY-XXXXXXXX
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('lab_order_id')->constrained('lab_orders')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->restrictOnDelete();
            
            $table->string('sample_type', 50); // Whole Blood, Serum, Plasma, Urine, Stool
            $table->string('container_type', 100); // EDTA (Purple), SST (Gold), Citrate (Blue)
            $table->string('status', 30)->default('pending_collection'); // pending_collection, collected, received, processing, completed, rejected
            
            $table->string('collection_site', 100)->nullable(); // Left Arm, Antecubital, Right Hand
            $table->timestamp('collected_at')->nullable();
            $table->foreignUuid('collected_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamp('received_at')->nullable(); // Received in Central Lab
            $table->foreignUuid('received_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamp('rejected_at')->nullable();
            $table->foreignUuid('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable(); // Hemolyzed, Clotted, Insufficient Quantity (QNS)
            
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('barcode');
            $table->index(['lab_order_id', 'status']);
            $table->index(['patient_id', 'status']);
            $table->index(['branch_id', 'status']);
        });

        // 4. Lab Results / Reports Table (Digital signing & versioned amendments)
        Schema::create('lab_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('report_number', 50)->unique(); // REP-YYYY-XXXXXX
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('lab_order_id')->constrained('lab_orders')->cascadeOnDelete();
            $table->foreignUuid('lab_test_id')->nullable()->constrained('lab_tests')->nullOnDelete();
            $table->foreignUuid('lab_sample_id')->nullable()->constrained('lab_samples')->nullOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->restrictOnDelete();
            
            // Technicians & Pathologist Sign-off
            $table->foreignUuid('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('pathologist_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('status', 30)->default('preliminary'); // preliminary, verified, signed, amended
            $table->boolean('has_abnormal_values')->default(false);
            $table->boolean('has_critical_values')->default(false);
            
            $table->timestamp('critical_acknowledged_at')->nullable();
            $table->timestamp('doctor_notified_at')->nullable();
            $table->string('doctor_notified_channel', 50)->nullable(); // telegram, sms, phone, system_alert
            
            $table->text('clinical_remarks')->nullable();
            $table->string('methodology', 150)->nullable();
            
            // Digital Signature Certification
            $table->string('digital_signature_hash', 255)->nullable();
            $table->timestamp('signed_at')->nullable();
            
            // Versioning and Append-Only Immutability
            $table->integer('version')->default(1);
            $table->boolean('is_amended')->default(false);
            $table->uuid('amended_from_id')->nullable();
            $table->text('amendment_reason')->nullable();
            
            // Lab Analyzer Equipment Interface
            $table->string('analyzer_device_id', 100)->nullable(); // Instrument ID (HL7/ASTM interface)

            $table->timestamps();
            $table->softDeletes();

            $table->index('report_number');
            $table->index(['lab_order_id', 'status']);
            $table->index(['patient_id', 'status']);
            $table->index('has_critical_values');
            $table->index('signed_at');
        });

        // Separate alter table for self-referencing foreign key to avoid PostgreSQL constraint race
        Schema::table('lab_results', function (Blueprint $table) {
            $table->foreign('amended_from_id')->references('id')->on('lab_results')->nullOnDelete();
        });

        // 5. Lab Result Parameter Items Table (Measured values & flags)
        Schema::create('lab_result_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lab_result_id')->constrained('lab_results')->cascadeOnDelete();
            $table->foreignUuid('reference_range_id')->nullable()->constrained('reference_ranges')->nullOnDelete();
            
            $table->string('parameter_name', 100);
            $table->string('measured_value', 100);
            $table->decimal('numeric_value', 10, 3)->nullable();
            $table->string('unit', 30)->nullable();
            $table->decimal('reference_low', 10, 3)->nullable();
            $table->decimal('reference_high', 10, 3)->nullable();
            
            // Auto-calculated flag based on reference ranges
            $table->string('flag', 20)->default('normal'); // normal, low, high, critical_low, critical_high, abnormal
            $table->string('notes', 255)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['lab_result_id', 'parameter_name']);
            $table->index('flag');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_result_items');
        Schema::dropIfExists('lab_results');
        Schema::dropIfExists('lab_samples');
        Schema::dropIfExists('reference_ranges');
        Schema::dropIfExists('lab_tests');
    }
};
