<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Public Marketing Leads.
     */
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 150);
            $table->string('email', 150);
            $table->string('phone', 50)->nullable();
            $table->string('hospital_name', 180);
            $table->string('hospital_type', 60)->default('general_hospital');
            $table->string('hospital_size', 50)->default('50_150');
            $table->integer('branches_count')->default(1);
            $table->jsonb('modules_of_interest')->default('[]');
            $table->string('status', 40)->default('new'); // new, contacted, demo_scheduled, qualified, closed
            $table->date('preferred_demo_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('source', 100)->default('website_landing_page');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('email');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
