<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->string('suffix', 20)->nullable();
            $table->date('date_of_birth');
            $table->enum('sex', ['Male', 'Female']);
            $table->string('civil_status', 50)->nullable();
            $table->string('government_id_type', 50)->nullable();
            $table->string('government_id_number', 100)->nullable();
            $table->string('contact_number', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('barangay', 100)->nullable();
            $table->string('municipality', 100)->nullable();
            $table->boolean('is_senior_citizen')->default(false);
            $table->boolean('is_pwd')->default(false);
            $table->boolean('is_student')->default(false);
            $table->boolean('is_government_employee')->default(false);
            $table->boolean('is_graduating_college')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['last_name', 'first_name'], 'idx_name');
            $table->index('date_of_birth', 'idx_dob');
            $table->index(['barangay', 'municipality'], 'idx_barangay');
            $table->index('government_id_number', 'idx_gov_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};
