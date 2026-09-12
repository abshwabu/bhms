<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Telegram Reporting module.
     */
    public function up(): void
    {
        // 1. Telegram Channels & Group Dispatch Registry
        Schema::create('telegram_channels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();

            $table->string('chat_id', 100); // e.g. -1001234567890 or @channel_handle or private chat ID
            $table->string('name', 150); // e.g. "Hospital Executive Admins", "ICU & Doctors Alert Feed"
            $table->string('role', 50); // admin, doctors, pharmacy, finance, nursing, emergency
            $table->string('bot_token_ref', 100)->default('TELEGRAM_BOT_TOKEN'); // Reference to env variable or specific token key

            // Subscriptions & RBAC command matrix
            $table->jsonb('allowed_report_types')->default('["daily_digest","critical_alerts"]');
            $table->jsonb('allowed_commands')->default('["/help","/beds","/digest"]');
            $table->jsonb('alert_thresholds')->default('{"icu_bed_min": 2, "low_stock_units": 15, "critical_lab": true}');

            $table->boolean('is_active')->default(true);
            $table->string('description', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['chat_id', 'role']);
            $table->index('role');
            $table->index('chat_id');
            $table->index('is_active');
        });

        // 2. Telegram Message Logs (Full immutable delivery & retry audit ledger)
        Schema::create('telegram_message_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('channel_id')->nullable()->constrained('telegram_channels')->nullOnDelete();
            $table->string('chat_id', 100);
            $table->string('role', 50)->nullable();
            $table->string('message_type', 50); // daily_digest, critical_alert, command_response, shift_handover, custom
            $table->string('direction', 20)->default('outbound'); // outbound, inbound_command

            $table->text('content');
            $table->string('parse_mode', 20)->default('HTML');
            $table->string('status', 30)->default('queued'); // queued, sent, failed, retrying

            $table->integer('retry_count')->default(0);
            $table->integer('max_retries')->default(3);
            $table->text('error_message')->nullable();
            $table->string('telegram_message_id', 100)->nullable();
            $table->jsonb('response_payload')->default('{}');

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['chat_id', 'status']);
            $table->index(['message_type', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_message_logs');
        Schema::dropIfExists('telegram_channels');
    }
};
