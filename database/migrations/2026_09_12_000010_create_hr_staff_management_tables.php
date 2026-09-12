<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for HR & Staff Management module.
     */
    public function up(): void
    {
        // 1. Staff Roles & Clinical Designations
        Schema::create('staff_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();

            $table->string('name', 100);
            $table->string('code', 50);
            $table->string('department', 50)->default('clinical'); // clinical, nursing, laboratory, radiology, pharmacy, billing, admin
            $table->boolean('is_medical')->default(false); // true if licensed healthcare provider
            $table->text('description')->nullable();

            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->index('department');
        });

        // 2. Staff Profiles
        Schema::create('staff', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('staff_role_id')->constrained('staff_roles')->restrictOnDelete();

            $table->string('employee_id', 50);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 100);
            $table->string('phone', 50)->nullable();
            $table->string('department', 50)->default('general');
            $table->string('designation', 100); // e.g. Senior Consultant, Charge Nurse, Radiographer
            $table->string('employment_type', 30)->default('full_time'); // full_time, part_time, contract, locum, intern
            $table->date('joining_date');
            $table->string('status', 30)->default('active'); // active, on_leave, suspended, resigned, terminated

            // Optional payroll integration hooks (integer cents)
            $table->integer('hourly_rate_cents')->default(0);
            $table->integer('monthly_salary_cents')->default(0);
            $table->jsonb('bank_details')->nullable();
            $table->jsonb('emergency_contact')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'employee_id']);
            $table->index(['branch_id', 'department']);
            $table->index(['branch_id', 'status']);
        });

        // 3. Credentials & Medical Licenses (With expiry tracking)
        Schema::create('credentials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('staff_id')->constrained('staff')->cascadeOnDelete();

            $table->string('credential_type', 50); // medical_license, board_certification, nursing_council, dea_registration, bls_acls
            $table->string('title', 150);
            $table->string('license_number', 100);
            $table->string('issuing_authority', 150);
            $table->date('issue_date');
            $table->date('expiry_date'); // Proactive alert to HR ahead of expiration
            $table->string('verification_status', 30)->default('active'); // active, expiring_soon, expired, pending_verification
            $table->string('document_url', 255)->nullable();
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['staff_id', 'expiry_date']);
            $table->index(['branch_id', 'verification_status']);
        });

        // 4. Shifts & Duty Rosters (With conflict & double-booking prevention)
        Schema::create('shifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('staff_id')->constrained('staff')->cascadeOnDelete();

            $table->string('shift_name', 100); // Morning Shift, Night ICU, Emergency On-Call
            $table->string('shift_type', 30)->default('morning'); // morning, evening, night, on_call, custom
            $table->string('department', 50);
            $table->date('shift_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamp('start_datetime');
            $table->timestamp('end_datetime');

            $table->string('status', 30)->default('scheduled'); // scheduled, in_progress, completed, cancelled, swapped
            $table->boolean('is_published')->default(false);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['staff_id', 'shift_date']);
            $table->index(['branch_id', 'shift_date']);
            $table->index(['start_datetime', 'end_datetime']);
        });

        // 5. Attendance Records (Clock In / Out & Punctuality)
        Schema::create('attendance', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignUuid('shift_id')->nullable()->constrained('shifts')->nullOnDelete();

            $table->date('date');
            $table->timestamp('check_in_time')->nullable();
            $table->timestamp('check_out_time')->nullable();
            $table->integer('total_minutes_worked')->default(0);
            $table->string('status', 30)->default('present'); // present, late, half_day, absent, on_leave
            $table->boolean('is_punctual')->default(true);
            $table->integer('minutes_late')->default(0);
            $table->string('check_in_ip', 50)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['staff_id', 'date']);
            $table->index(['branch_id', 'date']);
            $table->index(['staff_id', 'status']);
        });

        // 6. Leave Balances
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('staff_id')->constrained('staff')->cascadeOnDelete();

            $table->integer('year');
            $table->string('leave_type', 30); // annual, sick, maternity, paternity, casual, study
            $table->integer('allocated_days')->default(20);
            $table->integer('used_days')->default(0);
            $table->integer('pending_days')->default(0);
            $table->integer('remaining_days')->default(20);

            $table->timestamps();

            $table->unique(['staff_id', 'year', 'leave_type']);
            $table->index(['staff_id', 'year']);
        });

        // 7. Leave Requests & Approval Workflow
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('staff_id')->constrained('staff')->cascadeOnDelete();

            $table->string('leave_type', 30); // annual, sick, maternity, paternity, casual, study, unpaid
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('total_days');
            $table->text('reason');
            $table->string('status', 30)->default('pending'); // pending, approved, rejected, cancelled

            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('rejection_reason', 255)->nullable();
            $table->string('emergency_contact_phone', 50)->nullable();

            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['staff_id', 'start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('attendance');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('credentials');
        Schema::dropIfExists('staff');
        Schema::dropIfExists('staff_roles');
    }
};
