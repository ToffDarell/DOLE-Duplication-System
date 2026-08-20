<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Depends on: beneficiary_programs (created in 063250)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tupad_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficiary_program_id')->constrained('beneficiary_programs')->cascadeOnDelete();

            $table->string('project_location_barangay', 100)->nullable();
            $table->string('project_location_municipality', 100)->nullable();
            $table->string('project_location_province', 100)->nullable();
            $table->string('project_location_district', 20)->nullable();
            $table->string('epayment_account_no', 100)->nullable();
            $table->string('beneficiary_type', 100)->nullable();
            $table->string('occupation', 150)->nullable();
            $table->string('average_monthly_income', 50)->nullable();
            $table->string('dependent_name', 150)->nullable();
            $table->string('dependent_relationship', 50)->nullable();
            $table->boolean('interested_in_employment')->default(false);
            $table->string('employment_interest_detail', 150)->nullable();
            $table->string('skills_training_needed', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tupad_profiles');
    }
};
