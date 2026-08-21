<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dilp_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dilp_group_id')->constrained('dilp_groups')->cascadeOnDelete();
            $table->string('member_name', 150);
            $table->string('contact_no', 100)->nullable();
            $table->string('designation', 100)->nullable();
            $table->timestamps();

            $table->index('dilp_group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dilp_group_members');
    }
};
