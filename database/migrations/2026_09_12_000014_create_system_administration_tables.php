<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for System Administration module.
     */
    public function up(): void
    {
        // 1. Hospital Services Catalog (Master Data: Clinical, Diagnostic, Surgical, Consultation)
        Schema::create('services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('department_id')->nullable()->constrained('departments')->nullOnDelete();

            $table->string('code', 50); // e.g. "SRV-CONS-GEN", "SRV-ECG-12L", "SRV-CBC"
            $table->string('name', 150);
            $table->string('category', 50)->default('clinical'); // clinical, surgical, diagnostic, nursing, emergency, administrative
            $table->text('description')->nullable();
            $table->integer('duration_minutes')->default(15);
            $table->integer('base_price_cents')->default(0);
            $table->boolean('requires_doctor')->default(true);
            $table->boolean('is_active')->default(true);
            $table->jsonb('preparation_instructions')->default('[]');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'code']);
            $table->index(['branch_id', 'category']);
            $table->index(['branch_id', 'is_active']);
            $table->index('code');
        });

        // 2. Notification Templates (Multi-channel SMS, Email, Push message templates with variable tokens)
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->cascadeOnDelete(); // null = group-wide

            $table->string('code', 80); // appointment_confirmed, prescription_ready, critical_lab_alert, bill_invoice, triage_alert
            $table->string('name', 150);
            $table->string('channel', 30); // sms, email, push
            $table->string('subject', 255)->nullable();
            $table->text('body');
            $table->jsonb('available_variables')->default('[]'); // ['patient_name', 'appointment_date', 'doctor_name', 'hospital_name']
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['code', 'channel']);
            $table->index('branch_id');
        });

        // 3. Notification Logs (Audit trail of all sent, retrying, and failed notifications across providers)
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignUuid('template_id')->nullable()->constrained('notification_templates')->nullOnDelete();
            $table->foreignUuid('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('channel', 30); // sms, email, push
            $table->string('recipient', 255); // phone number, email address, or device push token
            $table->string('event_type', 80);
            $table->string('subject', 255)->nullable();
            $table->text('body');
            $table->jsonb('payload')->default('{}');

            $table->string('status', 30)->default('queued'); // queued, sent, failed, retrying
            $table->string('provider', 50)->default('mock_gateway'); // twilio, mailgun, smtp, fcm, mock_gateway
            $table->integer('retry_count')->default(0);
            $table->integer('max_retries')->default(3);
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['channel', 'status']);
            $table->index(['recipient', 'status']);
            $table->index('event_type');
            $table->index('created_at');
        });

        // 4. Backup & Disaster Recovery History
        Schema::create('backup_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('backup_type', 30)->default('database'); // database, full, config
            $table->string('file_path');
            $table->bigInteger('file_size_bytes')->default(0);
            $table->string('status', 30)->default('completed'); // completed, failed, verified, restored
            $table->string('checksum_sha256', 64)->nullable();
            $table->jsonb('metadata')->default('{}');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('restored_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });

        // 5. Enhance branches table with operational metadata if missing
        if (Schema::hasTable('branches') && !Schema::hasColumn('branches', 'timezone')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->string('timezone', 50)->default('UTC');
                $table->string('currency', 10)->default('USD');
                $table->boolean('is_main_branch')->default(false);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_logs');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('services');

        if (Schema::hasTable('branches') && Schema::hasColumn('branches', 'timezone')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropColumn(['timezone', 'currency', 'is_main_branch']);
            });
        }
    }
};
