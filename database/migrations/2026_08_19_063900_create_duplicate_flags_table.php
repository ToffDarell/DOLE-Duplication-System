<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duplicate_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficiary_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matched_beneficiary_id')->constrained('beneficiaries')->cascadeOnDelete();
            $table->integer('match_score');
            $table->enum('match_type', ['exact', 'high', 'medium', 'low']);
            $table->json('matched_fields')->nullable();
            $table->enum('status', ['pending', 'resolved_duplicate', 'resolved_not_duplicate', 'overridden'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duplicate_flags');
    }
};
