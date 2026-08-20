<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('duplicate_flags', function (Blueprint $table) {
            $table->boolean('household_match_flag')->default(false)->after('matched_fields');
        });
    }

    public function down(): void
    {
        Schema::table('duplicate_flags', function (Blueprint $table) {
            $table->dropColumn('household_match_flag');
        });
    }
};
