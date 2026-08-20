<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiary_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficiary_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained();
            $table->year('availment_year');
            $table->enum('enrollment_type', ['individual', 'group'])->nullable();
            $table->foreignId('dilp_group_id')->nullable()->constrained('dilp_groups')->nullOnDelete();
            $table->enum('internship_duration', ['6_months', '1_year'])->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('remarks')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['beneficiary_id', 'program_id', 'availment_year'], 'uniq_program_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiary_programs');
    }
};
