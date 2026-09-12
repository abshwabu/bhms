<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Compliance & Security module.
     */
    public function up(): void
    {
        // 1. Patient Consents Ledger (Tracks legal consent for procedures, data sharing, HIPAA notices)
        Schema::create('patient_consents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();

            $table->string('consent_type', 50); // treatment_general, surgical_procedure, data_sharing, telehealth, research_trial, hipaa_notice
            $table->string('title', 255);
            $table->text('purpose');
            $table->string('status', 30)->default('granted'); // granted, revoked, expired, pending

            $table->timestamp('granted_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->text('revocation_reason')->nullable();

            // PII & sensitive fields stored with column-level encryption (unreadable in raw DB dumps)
            $table->text('signature_data')->nullable(); // Encrypted base64 signature/biometric hash
            $table->text('patient_national_id')->nullable(); // Encrypted PII
            $table->text('contact_phone')->nullable(); // Encrypted PII
            $table->text('sensitive_notes')->nullable(); // Encrypted clinical consent disclosure notes

            // Verification & Legal Witness
            $table->string('witness_name', 150)->nullable();
            $table->foreignUuid('witness_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'consent_type']);
            $table->index(['branch_id', 'status']);
            $table->index('status');
        });

        // 2. HIPAA Compliance Checklist & Automated Safeguard Audits
        Schema::create('hipaa_compliance_checks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('check_category', 50); // access_control, audit_controls, integrity, transmission_security, consent_privacy
            $table->string('safeguard_code', 50); // 164.312(a)(1), 164.312(b), etc.
            $table->string('title', 255);
            $table->text('description');
            $table->string('status', 30)->default('compliant'); // compliant, warning, non_compliant
            $table->jsonb('details')->default('{}');
            $table->text('remediation_steps')->nullable();
            $table->timestamp('last_evaluated_at')->useCurrent();
            $table->timestamps();

            $table->unique('safeguard_code');
            $table->index('check_category');
            $table->index('status');
        });

        // 3. Ensure PII column in patients table can store encrypted payloads
        if (Schema::hasTable('patients') && Schema::hasColumn('patients', 'passport_number')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->text('passport_number')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hipaa_compliance_checks');
        Schema::dropIfExists('patient_consents');
    }
};
