<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dilp_groups', function (Blueprint $table) {
            $table->id();
            $table->string('group_name', 150);
            $table->string('co_partner_name', 150)->nullable();
            $table->string('co_partner_contact', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dilp_groups');
    }
};
