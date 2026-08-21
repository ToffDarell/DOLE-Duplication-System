<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dilp_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dilp_group_id')->nullable()->constrained('dilp_groups')->nullOnDelete();
            $table->string('project_name', 200);
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('liquidation_status', ['pending', 'partial', 'liquidated', 'overdue', 'unliquidated'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dilp_projects');
    }
};
