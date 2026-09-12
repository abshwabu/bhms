<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Super Admin Platform Management.
     */
    public function up(): void
    {
        // 1. Add is_super_admin to users if not present
        if (!Schema::hasColumn('users', 'is_super_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_super_admin')->default(false)->after('is_patient');
            });
        }

        // 2. Add subscription/suspension attributes to organizations
        if (!Schema::hasColumn('organizations', 'plan_tier')) {
            Schema::table('organizations', function (Blueprint $table) {
                $table->string('plan_tier', 50)->default('community')->after('settings');
                $table->string('subscription_status', 50)->default('active')->after('plan_tier');
                $table->timestamp('suspended_at')->nullable()->after('subscription_status');
                $table->text('suspension_reason')->nullable()->after('suspended_at');
            });
        }

        // 3. Subscriptions Oversight
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('plan_tier', 50)->default('community'); // community, regional, enterprise
            $table->string('status', 50)->default('active'); // active, trialing, past_due, suspended, cancelled
            $table->string('billing_cycle', 20)->default('monthly'); // monthly, annual
            $table->integer('amount_cents')->default(49900);
            $table->string('currency', 10)->default('USD');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start')->useCurrent();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('payment_method_last4', 10)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
            $table->index('status');
        });

        // 4. Feature Flags Master Catalog
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key', 100)->unique();
            $table->string('name', 150);
            $table->string('category', 50)->default('clinical'); // clinical, operations, intelligence, integrations
            $table->text('description')->nullable();
            $table->boolean('is_globally_enabled')->default(true);
            $table->jsonb('default_enabled_plans')->default('["regional","enterprise"]');
            $table->timestamps();

            $table->index('category');
            $table->index('is_globally_enabled');
        });

        // 5. Tenant-Specific Feature Flag Overrides
        Schema::create('tenant_feature_flags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('feature_flag_id')->constrained('feature_flags')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->jsonb('custom_config')->default('{}');
            $table->timestamps();

            $table->unique(['organization_id', 'feature_flag_id']);
            $table->index(['organization_id', 'is_enabled']);
        });

        // 6. Support Tickets & Helpdesk Issue Log
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ticket_number', 50)->unique();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('reporter_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reporter_name', 150);
            $table->string('reporter_email', 150);
            $table->string('subject', 255);
            $table->text('description');
            $table->string('category', 50)->default('system'); // billing, clinical, integration, bug, feature_request
            $table->string('priority', 30)->default('medium'); // low, medium, high, critical
            $table->string('status', 30)->default('open'); // open, in_progress, waiting_on_client, resolved, closed
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
            $table->index(['status', 'priority']);
        });

        // 7. Global Support Impersonation Audit Logs
        Schema::create('impersonation_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('super_admin_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('target_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('token_id', 100)->nullable();
            $table->string('reason', 255);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['super_admin_id', 'is_active']);
            $table->index(['organization_id', 'created_at']);
        });

        // 8. Platform Announcements (Broadcasts to all hospital clients)
        Schema::create('platform_announcements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 255);
            $table->text('content');
            $table->string('severity', 30)->default('info'); // info, warning, critical, maintenance
            $table->jsonb('target_plans')->default('["*"]');
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'starts_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_announcements');
        Schema::dropIfExists('impersonation_logs');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('tenant_feature_flags');
        Schema::dropIfExists('feature_flags');
        Schema::dropIfExists('subscriptions');

        if (Schema::hasColumn('organizations', 'plan_tier')) {
            Schema::table('organizations', function (Blueprint $table) {
                $table->dropColumn(['plan_tier', 'subscription_status', 'suspended_at', 'suspension_reason']);
            });
        }

        if (Schema::hasColumn('users', 'is_super_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_super_admin');
            });
        }
    }
};
