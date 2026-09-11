<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Sequence table for atomic, gap-free, non-reusable MRN generation per branch per year
        Schema::create('mrn_sequences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->integer('year');
            $table->string('prefix', 20)->default('MRN');
            $table->bigInteger('current_sequence')->default(0);
            $table->timestamps();

            $table->unique(['branch_id', 'year', 'prefix']);
        });

        // 2. Main Patients Table
        Schema::create('patients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->string('mrn', 50)->unique();
            
            // Intake & Registration classification
            $table->string('registration_type', 30)->default('walk_in'); // walk_in, referral, emergency
            $table->string('triage_level', 20)->nullable(); // critical, urgent, standard, non_urgent
            $table->string('referral_source', 255)->nullable(); // Doctor, clinic, hospital name or facility

            // Core Demographics
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100)->nullable(); // Can be nullable for unknown emergency intake
            $table->date('date_of_birth')->nullable();
            $table->boolean('is_dob_estimated')->default(false);
            $table->string('gender', 20)->default('unknown'); // male, female, other, unknown
            $table->string('blood_group', 10)->nullable(); // A+, A-, B+, B-, AB+, AB-, O+, O-
            
            // Identification & Contact PII
            $table->string('national_id', 100)->nullable();
            $table->string('passport_number', 100)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('alternate_phone', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('marital_status', 30)->nullable(); // single, married, divorced, widowed
            $table->string('occupation', 100)->nullable();
            $table->string('preferred_language', 50)->default('English');

            // Structured JSONB PII fields
            $table->jsonb('address')->default('{}'); // street, city, state, postal_code, country
            $table->jsonb('emergency_contact')->default('{}'); // name, relationship, phone, alt_phone

            // Patient Portal linkage
            $table->foreignUuid('portal_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Status & Auditing
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for lightning-fast queries (<500ms for 100k+ records)
            $table->index('branch_id');
            $table->index('organization_id');
            $table->index('mrn');
            $table->index('phone');
            $table->index('national_id');
            $table->index('registration_type');
            $table->index('created_at');
        });

        // PostgreSQL Trigram GIN index for fuzzy and exact full name search
        DB::statement("CREATE INDEX idx_patients_trgm_name ON patients USING gin ((coalesce(first_name, '') || ' ' || coalesce(last_name, '')) gin_trgm_ops);");
        DB::statement("CREATE INDEX idx_patients_trgm_national_id ON patients USING gin (coalesce(national_id, '') gin_trgm_ops);");
        DB::statement("CREATE INDEX idx_patients_trgm_phone ON patients USING gin (coalesce(phone, '') gin_trgm_ops);");

        // 3. Patient Medical History Table
        Schema::create('patient_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            
            $table->string('category', 50); // chronic_condition, past_illness, surgical_history, family_history, social_history, medication_history
            $table->string('condition_or_procedure', 255);
            $table->string('icd10_code', 20)->nullable();
            $table->date('diagnosed_date')->nullable();
            $table->string('status', 50)->default('active'); // active, resolved, managed, recurrent
            $table->string('severity', 30)->nullable(); // mild, moderate, severe
            $table->text('notes')->nullable();
            
            $table->foreignUuid('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('patient_id');
            $table->index('category');
            $table->index('status');
        });

        // 4. Patient Allergies Table
        Schema::create('patient_allergies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();

            $table->string('allergen', 255); // Penicillin, Aspirin, Peanuts, Latex, Dust
            $table->string('allergen_type', 50)->default('drug'); // drug, food, environmental, biological, other
            $table->string('reaction', 255); // Anaphylaxis, Rash, Bronchospasm, Vomiting
            $table->string('severity', 30)->default('moderate'); // mild, moderate, severe, life_threatening
            $table->string('status', 30)->default('active'); // active, suspected, resolved
            $table->date('diagnosed_at')->nullable();
            $table->text('notes')->nullable();

            $table->foreignUuid('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('patient_id');
            $table->index('severity');
            $table->index('status');
        });

        // 5. Patient Insurance Table
        Schema::create('patient_insurance', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();

            $table->string('provider_name', 255);
            $table->string('policy_number', 100);
            $table->string('group_number', 100)->nullable();
            $table->string('coverage_type', 50)->default('primary'); // primary, secondary, tertiary
            $table->decimal('coverage_percentage', 5, 2)->default(100.00); // 100.00 = full coverage
            $table->integer('copay_amount_cents')->default(0);
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->boolean('pre_auth_required')->default(false);
            $table->string('status', 30)->default('active'); // active, expired, cancelled, pending_verification
            $table->string('card_image_path', 500)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('patient_id');
            $table->index('policy_number');
            $table->index('status');
        });

        // 6. Patient Relationships / Family / Dependents Table
        Schema::create('patient_relationships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('related_patient_id')->nullable()->constrained('patients')->nullOnDelete();

            $table->string('relationship_type', 50); // parent, child, spouse, guardian, sibling, caregiver, other
            $table->boolean('is_guardian')->default(false);
            $table->boolean('is_emergency_contact')->default(false);
            $table->boolean('is_billing_guarantor')->default(false);

            // In case the relative is not yet a registered patient in the hospital system
            $table->string('external_name', 255)->nullable();
            $table->string('external_phone', 50)->nullable();
            $table->string('external_national_id', 100)->nullable();
            $table->jsonb('external_address')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('patient_id');
            $table->index('related_patient_id');
            $table->index('relationship_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_relationships');
        Schema::dropIfExists('patient_insurance');
        Schema::dropIfExists('patient_allergies');
        Schema::dropIfExists('patient_history');
        Schema::dropIfExists('patients');
        Schema::dropIfExists('mrn_sequences');
    }
};
