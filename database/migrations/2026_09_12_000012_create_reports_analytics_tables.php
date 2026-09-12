<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Reports & Analytics pre-aggregation layer.
     */
    public function up(): void
    {
        // 1. Pre-aggregated Daily Hospital KPIs (Enables <2s dashboard load for 100k+ records)
        Schema::create('daily_hospital_kpis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->date('report_date');

            // Patient Flow Metrics
            $table->integer('total_registered_patients')->default(0);
            $table->integer('new_patients_today')->default(0);
            $table->integer('total_opd_visits')->default(0);
            $table->integer('total_admissions')->default(0);
            $table->integer('total_discharges')->default(0);
            $table->integer('active_inpatients')->default(0);

            // Bed Occupancy Metrics
            $table->integer('total_beds')->default(0);
            $table->integer('occupied_beds')->default(0);
            $table->decimal('occupancy_rate_percentage', 5, 2)->default(0.00);
            $table->decimal('average_length_of_stay_days', 5, 2)->default(0.00);

            // Emergency & Trauma Metrics
            $table->integer('total_emergency_cases')->default(0);
            $table->integer('emergency_resus_cases')->default(0);

            // Financial Revenue & Collection Metrics (tracked in integer cents)
            $table->bigInteger('total_invoiced_cents')->default(0);
            $table->bigInteger('total_collected_cents')->default(0);
            $table->bigInteger('total_outstanding_cents')->default(0);

            // Clinical Volume
            $table->integer('total_prescriptions')->default(0);
            $table->integer('total_lab_orders')->default(0);
            $table->integer('total_radiology_orders')->default(0);

            // Department & Payment Breakdown Fast Caches
            $table->jsonb('department_breakdown')->default('{}');
            $table->jsonb('payment_mode_breakdown')->default('{}');
            $table->jsonb('metadata')->default('{}');

            $table->timestamps();

            $table->unique(['branch_id', 'report_date']);
            $table->index(['branch_id', 'report_date']);
            $table->index('report_date');
        });

        // 2. Pre-aggregated Department Daily Metrics
        Schema::create('department_daily_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->date('report_date');

            $table->foreignUuid('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('department_name', 100);

            $table->integer('patient_count')->default(0);
            $table->integer('opd_consultations_count')->default(0);
            $table->integer('admissions_count')->default(0);
            $table->integer('discharges_count')->default(0);
            $table->integer('occupied_beds')->default(0);
            $table->integer('total_beds')->default(0);
            $table->decimal('occupancy_rate', 5, 2)->default(0.00);
            $table->decimal('average_length_of_stay_days', 5, 2)->default(0.00);
            $table->integer('lab_orders_count')->default(0);
            $table->integer('radiology_orders_count')->default(0);
            $table->bigInteger('revenue_cents')->default(0);

            $table->timestamps();

            $table->unique(['branch_id', 'report_date', 'department_name']);
            $table->index(['branch_id', 'report_date']);
            $table->index(['department_name', 'report_date']);
        });

        // 3. Pre-aggregated Doctor Daily Performance Metrics
        Schema::create('doctor_daily_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->date('report_date');

            $table->foreignUuid('doctor_id')->constrained('users')->cascadeOnDelete();
            $table->string('doctor_name', 150);
            $table->string('specialty', 100)->default('General Medicine');
            $table->string('department_name', 100)->default('general');

            $table->integer('patients_seen_count')->default(0);
            $table->integer('appointments_scheduled')->default(0);
            $table->integer('appointments_completed')->default(0);
            $table->integer('appointments_cancelled')->default(0);
            $table->integer('prescriptions_written_count')->default(0);
            $table->integer('lab_orders_placed_count')->default(0);
            $table->integer('radiology_orders_placed_count')->default(0);
            $table->integer('inpatient_admissions_count')->default(0);
            $table->bigInteger('revenue_generated_cents')->default(0);
            $table->decimal('average_consultation_minutes', 5, 2)->default(15.00);

            $table->timestamps();

            $table->unique(['branch_id', 'report_date', 'doctor_id']);
            $table->index(['branch_id', 'report_date']);
            $table->index(['doctor_id', 'report_date']);
        });

        // 4. Saved Custom Reports Catalog
        Schema::create('saved_custom_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('entity', 50); // invoices, appointments, admissions, patients, prescriptions, lab_orders, etc.
            $table->jsonb('selected_fields')->default('[]');
            $table->jsonb('filters')->default('[]');
            $table->string('sort_field', 100)->nullable();
            $table->string('sort_direction', 10)->default('desc');
            $table->string('group_by', 100)->nullable();
            $table->string('date_range_preset', 50)->default('last_30_days');
            $table->boolean('is_public')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'entity']);
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_custom_reports');
        Schema::dropIfExists('doctor_daily_metrics');
        Schema::dropIfExists('department_daily_metrics');
        Schema::dropIfExists('daily_hospital_kpis');
    }
};
