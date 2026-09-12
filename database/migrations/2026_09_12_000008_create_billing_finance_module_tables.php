<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Billing & Finance module.
     */
    public function up(): void
    {
        // 1. Price Lists & Master Procedure/Package Pricing Catalog
        Schema::create('price_lists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();

            $table->string('code', 50);
            $table->string('name', 150);
            $table->string('category', 50)->default('procedure'); // consultation, procedure, laboratory, radiology, nursing, bed, package, other
            $table->string('department', 100)->default('general');
            $table->integer('unit_price_cents')->default(0);

            $table->boolean('is_package')->default(false);
            $table->jsonb('package_items')->default('[]'); // Array of {code, name, quantity, standard_price_cents}
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'code']);
            $table->index(['branch_id', 'category']);
            $table->index(['branch_id', 'department']);
            $table->index('code');
        });

        // 2. Invoices (OPD, IPD, Pharmacy, Emergency)
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('invoice_number', 50)->unique();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();

            $table->foreignUuid('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignUuid('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignUuid('admission_id')->nullable()->constrained('admissions')->nullOnDelete();
            $table->foreignUuid('doctor_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('department', 100)->default('general');
            $table->string('billing_type', 30)->default('opd'); // opd, ipd, pharmacy, emergency, diagnostic
            $table->string('status', 30)->default('unpaid'); // draft, unpaid, partially_paid, paid, cancelled, refunded

            // Exact integer cents financial tracking (guarantees exact reconciliation with zero rounding drift)
            $table->integer('subtotal_cents')->default(0);
            $table->integer('discount_cents')->default(0);
            $table->integer('tax_cents')->default(0);
            $table->integer('total_cents')->default(0);
            $table->integer('paid_cents')->default(0);
            $table->integer('balance_cents')->default(0);

            $table->string('payment_terms', 50)->default('due_on_receipt');
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();

            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('invoice_number');
            $table->index(['patient_id', 'status']);
            $table->index(['branch_id', 'billing_type']);
            $table->index(['branch_id', 'status']);
            $table->index(['doctor_id', 'created_at']);
            $table->index(['department', 'created_at']);
        });

        // 3. Invoice Itemized Line Charges
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->constrained('invoices')->cascadeOnDelete();

            $table->string('item_type', 50); // consultation, procedure, laboratory, radiology, drug, bed_charge, nursing, package, other
            $table->string('item_code', 50)->nullable();
            $table->string('description', 255);
            $table->integer('quantity')->default(1);
            $table->integer('unit_price_cents')->default(0);
            $table->integer('subtotal_cents')->default(0);
            $table->integer('discount_cents')->default(0);
            $table->integer('total_cents')->default(0);

            $table->foreignUuid('doctor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('department', 100)->default('general');

            $table->string('reference_type', 100)->nullable(); // lab_order, radiology_order, dispensing_record_item, admission_bed
            $table->uuid('reference_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['invoice_id', 'item_type']);
            $table->index('doctor_id');
            $table->index('department');
        });

        // 4. Payments (Cash, Card, Mobile Money, Insurance, Bank Transfer)
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('receipt_number', 50)->unique();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();

            $table->foreignUuid('invoice_id')->constrained('invoices')->restrictOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->restrictOnDelete();

            $table->string('payment_mode', 30); // cash, card, mobile_money, insurance, bank_transfer
            $table->integer('amount_cents');
            $table->string('transaction_reference', 100)->nullable();
            $table->text('notes')->nullable();

            $table->foreignUuid('cashier_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 30)->default('completed'); // completed, reversed, refunded
            $table->timestamp('received_at');

            $table->timestamps();
            $table->softDeletes();

            $table->index('receipt_number');
            $table->index(['invoice_id', 'status']);
            $table->index(['patient_id', 'received_at']);
            $table->index('payment_mode');
        });

        // 5. Discounts & Approval Workflow
        Schema::create('discounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('invoice_id')->constrained('invoices')->cascadeOnDelete();

            $table->string('discount_type', 30)->default('fixed'); // fixed, percentage
            $table->decimal('percentage', 5, 2)->nullable();
            $table->integer('amount_cents'); // Exact discount in cents
            $table->string('reason', 255);

            $table->boolean('requires_approval')->default(false);
            $table->string('status', 30)->default('approved'); // pending_approval, approved, rejected

            $table->foreignUuid('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['invoice_id', 'status']);
            $table->index('status');
        });

        // 6. Refunds & Approval Workflow
        Schema::create('refunds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('refund_number', 50)->unique();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();

            $table->foreignUuid('invoice_id')->constrained('invoices')->restrictOnDelete();
            $table->foreignUuid('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->restrictOnDelete();

            $table->integer('amount_cents');
            $table->string('refund_mode', 30)->default('cash'); // cash, card, mobile_money, bank_transfer, credit_note
            $table->string('reason', 255);

            $table->boolean('requires_approval')->default(false);
            $table->string('status', 30)->default('approved'); // pending_approval, approved, rejected, processed

            $table->foreignUuid('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('refund_number');
            $table->index(['invoice_id', 'status']);
            $table->index('status');
        });

        // 7. Insurance & TPA Claims Management
        Schema::create('insurance_claims', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('claim_number', 50)->unique();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();

            $table->foreignUuid('invoice_id')->constrained('invoices')->restrictOnDelete();
            $table->foreignUuid('patient_insurance_id')->constrained('patient_insurance')->restrictOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->restrictOnDelete();

            $table->string('provider_name', 150);
            $table->string('policy_number', 100);
            $table->string('pre_auth_number', 100)->nullable();

            $table->integer('claimed_amount_cents');
            $table->integer('approved_amount_cents')->default(0);
            $table->integer('copay_amount_cents')->default(0);
            $table->integer('deductible_amount_cents')->default(0);

            $table->string('status', 30)->default('submitted'); // draft, submitted, under_review, approved, rejected, reconciled
            $table->date('submission_date')->nullable();
            $table->date('settlement_date')->nullable();
            $table->text('adjudication_notes')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->foreignUuid('submitted_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('claim_number');
            $table->index(['invoice_id', 'status']);
            $table->index('status');
            $table->index('provider_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_claims');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('discounts');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('price_lists');
    }
};
