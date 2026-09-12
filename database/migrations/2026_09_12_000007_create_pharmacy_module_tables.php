<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Pharmacy Module.
     */
    public function up(): void
    {
        // 1. Drugs Catalog Table (SKU-level tracking)
        Schema::create('drugs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sku', 50)->unique(); // e.g. DRG-AMX-500, DRG-MET-500
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();

            $table->string('brand_name', 150);
            $table->string('generic_name', 150);
            $table->string('form', 50)->default('tablet'); // tablet, capsule, syrup, injection, inhaler, drops
            $table->string('strength', 100); // 500 mg, 10 mg/ml, etc.
            $table->string('unit_of_measure', 50)->default('tablet'); // tablet, bottle, vial, ampoule, box

            // Configurable inventory thresholds
            $table->integer('reorder_threshold')->default(50); // Low stock alert fires at or below this count
            $table->integer('target_stock_level')->default(200);

            // Pricing
            $table->integer('unit_cost_cents')->default(0);
            $table->integer('unit_price_cents')->default(0);

            // Regulation & safety
            $table->boolean('is_prescription_required')->default(true);
            $table->boolean('is_controlled_substance')->default(false);
            $table->boolean('is_active')->default(true);

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('sku');
            $table->index('generic_name');
            $table->index('brand_name');
            $table->index(['branch_id', 'is_active']);
        });

        // 2. Drug Batches Table (Lot & Expiry Tracking for FEFO)
        Schema::create('drug_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('drug_id')->constrained('drugs')->cascadeOnDelete();

            $table->string('batch_number', 100); // Lot number e.g. LOT-2026-AMX-001
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date'); // Key for First-Expiry-First-Out (FEFO)

            $table->integer('quantity_received');
            $table->integer('quantity_on_hand');
            $table->integer('unit_cost_cents')->default(0);

            $table->string('supplier_name', 150)->nullable();
            $table->string('status', 30)->default('active'); // active, depleted, quarantined, expired

            $table->timestamps();
            $table->softDeletes();

            $table->index(['drug_id', 'expiry_date']);
            $table->index(['drug_id', 'status']);
            $table->index('batch_number');
            $table->index('expiry_date');
        });

        // 3. Drug Stock Movements Table (Comprehensive Audit Trail)
        Schema::create('drug_stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('drug_id')->constrained('drugs')->cascadeOnDelete();
            $table->foreignUuid('drug_batch_id')->nullable()->constrained('drug_batches')->nullOnDelete();

            $table->string('movement_type', 50); // intake, dispense, adjustment_addition, adjustment_deduction, waste, return
            $table->integer('quantity'); // Positive for additions, negative for deductions
            $table->integer('quantity_before');
            $table->integer('quantity_after');

            $table->string('reference_type', 100)->nullable(); // dispensing_record, purchase_order, manual_adjustment
            $table->uuid('reference_id')->nullable();
            $table->text('reason')->nullable();

            $table->foreignUuid('performed_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['drug_id', 'created_at']);
            $table->index(['drug_batch_id', 'created_at']);
            $table->index('movement_type');
        });

        // 4. Dispensing Records Table (Prescription Dispensation Linked to Clinical Prescriptions)
        Schema::create('dispensing_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('dispensation_number', 50)->unique(); // DSP-2026-XXXXXXXX
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('prescription_id')->constrained('prescriptions')->restrictOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignUuid('pharmacist_id')->constrained('users')->restrictOnDelete();

            $table->string('status', 30)->default('completed'); // completed, cancelled, partial
            $table->boolean('has_interaction_warnings')->default(false);
            $table->jsonb('interaction_alerts')->default('[]');

            $table->text('pharmacist_notes')->nullable();
            $table->text('counseling_notes')->nullable(); // Patient counseling on dosing / food interactions
            $table->timestamp('dispensed_at');

            $table->timestamps();
            $table->softDeletes();

            $table->index('dispensation_number');
            $table->index(['prescription_id', 'status']);
            $table->index(['patient_id', 'dispensed_at']);
            $table->index('pharmacist_id');
        });

        // 5. Dispensing Record Items Table (Batch-level allocations per prescribed item)
        Schema::create('dispensing_record_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('dispensing_record_id')->constrained('dispensing_records')->cascadeOnDelete();
            $table->foreignUuid('prescription_item_id')->nullable()->constrained('prescription_items')->nullOnDelete();
            $table->foreignUuid('drug_id')->constrained('drugs')->restrictOnDelete();
            $table->foreignUuid('drug_batch_id')->constrained('drug_batches')->restrictOnDelete();

            $table->integer('quantity_dispensed');
            $table->string('directions', 255)->nullable();
            $table->integer('unit_price_cents')->default(0);
            $table->integer('total_price_cents')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['dispensing_record_id', 'drug_id']);
            $table->index('drug_batch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispensing_record_items');
        Schema::dropIfExists('dispensing_records');
        Schema::dropIfExists('drug_stock_movements');
        Schema::dropIfExists('drug_batches');
        Schema::dropIfExists('drugs');
    }
};
