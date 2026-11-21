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
        Schema::table('dilp_projects', function (Blueprint $table) {
            $table->string('project_type', 20)->default('group')->after('id');
            $table->foreignId('beneficiary_id')->nullable()->after('dilp_group_id')->constrained('beneficiaries')->nullOnDelete();
            $table->string('beneficiary_name', 200)->nullable()->after('beneficiary_id');
            $table->decimal('grant_amount', 14, 2)->nullable()->after('description');
            $table->string('implementation_mode', 20)->nullable()->after('grant_amount');
            $table->string('acp_name', 200)->nullable()->after('implementation_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dilp_projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('beneficiary_id');
            $table->dropColumn([
                'project_type',
                'beneficiary_name',
                'grant_amount',
                'implementation_mode',
                'acp_name',
            ]);
        });
    }
};
