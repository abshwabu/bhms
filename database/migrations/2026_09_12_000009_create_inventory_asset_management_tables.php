<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Inventory & Asset Management module.
     */
    public function up(): void
    {
        // 1. Vendors (Suppliers for medical supplies, equipment & maintenance)
        Schema::create('vendors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();

            $table->string('vendor_code', 50);
            $table->string('name', 150);
            $table->string('contact_name', 100)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('tax_id', 50)->nullable();
            $table->jsonb('address')->nullable();
            $table->string('payment_terms', 50)->default('net_30'); // immediate, net_15, net_30, net_60
            $table->decimal('rating', 3, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'vendor_code']);
            $table->index(['branch_id', 'name']);
        });

        // 2. Inventory Items (Medical & Non-Medical, separate from pharmacy drugs)
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();

            $table->string('item_code', 50);
            $table->string('name', 150);
            $table->string('category', 50)->default('medical'); // medical, non_medical
            $table->string('sub_category', 50)->nullable(); // surgical_supplies, ppe, wound_care, linens, stationery, sanitation, it_supplies
            $table->string('unit_of_measure', 30)->default('piece'); // piece, box, pack, carton, roll, bottle, set
            $table->integer('current_stock')->default(0);
            $table->integer('min_stock_level')->default(10); // Low stock alert threshold
            $table->integer('max_stock_level')->nullable();
            $table->integer('reorder_quantity')->default(50);
            $table->integer('unit_cost_cents')->default(0); // in integer cents

            $table->foreignUuid('default_vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->string('storage_location', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'item_code']);
            $table->index(['branch_id', 'category']);
            $table->index(['branch_id', 'current_stock']);
        });

        // 3. Purchase Orders
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('po_number', 50)->unique();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('vendor_id')->constrained('vendors')->restrictOnDelete();

            // Approval chain states: draft -> submitted -> approved/rejected -> partially_received -> received -> cancelled
            $table->string('status', 30)->default('draft');
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();

            $table->integer('subtotal_cents')->default(0);
            $table->integer('tax_cents')->default(0);
            $table->integer('shipping_cost_cents')->default(0);
            $table->integer('total_cents')->default(0);
            $table->string('currency', 10)->default('USD');

            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('rejection_reason', 255)->nullable();

            $table->text('terms_and_conditions')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status']);
            $table->index(['branch_id', 'vendor_id']);
        });

        // 4. Purchase Order Line Items
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignUuid('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();

            $table->integer('quantity_ordered');
            $table->integer('quantity_received')->default(0);
            $table->integer('unit_cost_cents')->default(0);
            $table->integer('total_cost_cents')->default(0);
            $table->string('notes', 255)->nullable();

            $table->timestamps();

            $table->index(['purchase_order_id', 'inventory_item_id']);
        });

        // 5. Inventory Stock Movements & Consumption Logs (Guarantees audit reconciliation)
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();

            $table->string('movement_type', 40); // purchase_receipt, consumption, adjustment_addition, adjustment_reduction, return_to_vendor, transfer
            $table->integer('quantity'); // Positive for receipts/additions, negative for reductions/consumption
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->integer('unit_cost_cents')->nullable();
            $table->integer('total_cost_cents')->nullable();

            $table->string('reference_type', 50)->nullable(); // purchase_order, manual_adjustment, department_requisition
            $table->uuid('reference_id')->nullable();
            $table->string('department', 100)->nullable(); // emergency, ot, icu, general_ward, etc.
            $table->foreignUuid('performed_by')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['branch_id', 'inventory_item_id']);
            $table->index(['branch_id', 'movement_type']);
            $table->index(['reference_type', 'reference_id']);
        });

        // 6. Hospital Equipment & Biomedical Assets
        Schema::create('equipment', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();

            $table->string('asset_tag', 50);
            $table->string('name', 150);
            $table->string('category', 50)->default('biomedical'); // biomedical, laboratory, radiology, surgical, it_hardware, facility
            $table->string('model_number', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->string('manufacturer', 100)->nullable();
            $table->foreignUuid('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();

            $table->string('department', 100)->default('general'); // icu, emergency, ot, radiology, laboratory, etc.
            $table->string('room_location', 100)->nullable();
            $table->date('purchase_date')->nullable();
            $table->integer('purchase_cost_cents')->default(0);
            $table->date('warranty_expiry_date')->nullable();

            $table->string('status', 30)->default('operational'); // operational, under_maintenance, out_of_order, decommissioned
            $table->string('criticality', 20)->default('medium'); // critical, high, medium, low
            $table->integer('maintenance_frequency_days')->default(90); // e.g. every 90 days for quarterly PM
            $table->date('last_maintenance_date')->nullable();
            $table->date('next_maintenance_date')->nullable(); // Used for proactive reminders/alerts

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'asset_tag']);
            $table->index(['branch_id', 'status']);
            $table->index(['branch_id', 'next_maintenance_date']);
            $table->index(['branch_id', 'department']);
        });

        // 7. Equipment Maintenance Schedules & Service Logs
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('equipment_id')->constrained('equipment')->cascadeOnDelete();

            $table->string('log_number', 50)->unique();
            $table->string('maintenance_type', 40)->default('preventive'); // preventive, corrective_repair, calibration, safety_inspection
            $table->string('status', 30)->default('scheduled'); // scheduled, in_progress, completed, cancelled
            $table->string('priority', 20)->default('medium'); // low, medium, high, urgent

            $table->date('scheduled_date');
            $table->date('completed_date')->nullable();
            $table->string('technician_name', 100)->nullable();
            $table->foreignUuid('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignUuid('performed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->integer('cost_cents')->default(0);
            $table->text('findings')->nullable();
            $table->text('actions_taken')->nullable();
            $table->jsonb('parts_replaced')->nullable();
            $table->date('next_recommended_date')->nullable();

            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['equipment_id', 'scheduled_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_logs');
        Schema::dropIfExists('equipment');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('vendors');
    }
};
