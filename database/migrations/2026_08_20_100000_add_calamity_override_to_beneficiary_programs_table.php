<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beneficiary_programs', function (Blueprint $table) {
            if (! Schema::hasColumn('beneficiary_programs', 'is_calamity_override')) {
                $table->boolean('is_calamity_override')->default(false)->after('internship_duration');
            }
            if (! Schema::hasColumn('beneficiary_programs', 'calamity_remarks')) {
                $table->text('calamity_remarks')->nullable()->after('is_calamity_override');
            }
            $table->index('beneficiary_id', 'beneficiary_programs_beneficiary_id_index');
            $table->dropUnique('uniq_program_year');
        });
    }

    public function down(): void
    {
        Schema::table('beneficiary_programs', function (Blueprint $table) {
            $table->unique(['beneficiary_id', 'program_id', 'availment_year'], 'uniq_program_year');
            $table->dropIndex('beneficiary_programs_beneficiary_id_index');
            $table->dropColumn(['is_calamity_override', 'calamity_remarks']);
        });
    }
};
