<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_id')->nullable()->unique()->after('name');
            $table->string('contact_number', 20)->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('contact_number');
            $table->boolean('must_reset_password')->default(false)->after('is_active');
            $table->timestamp('last_activity_at')->nullable()->after('must_reset_password');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'employee_id',
                'contact_number',
                'is_active',
                'must_reset_password',
                'last_activity_at',
            ]);
            $table->dropSoftDeletes();
        });
    }
};
