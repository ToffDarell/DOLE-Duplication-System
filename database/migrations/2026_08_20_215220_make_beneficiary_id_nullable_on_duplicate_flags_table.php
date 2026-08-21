<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('duplicate_flags', function (Blueprint $table) {
            $table->dropForeign(['beneficiary_id']);
            $table->foreignId('beneficiary_id')->nullable()->change()->constrained('beneficiaries')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('duplicate_flags', function (Blueprint $table) {
            $table->dropForeign(['beneficiary_id']);
            $table->foreignId('beneficiary_id')->nullable(false)->change()->constrained('beneficiaries')->cascadeOnDelete();
        });
    }
};
