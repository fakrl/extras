<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_project_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('casting_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(); // RF-42: admin/sub-role yang ditugaskan
            $table->foreignId('assigned_by')->constrained('users'); // Super Admin
            $table->enum('status_log', ['berjalan', 'selesai'])->default('berjalan'); // RF-45
            $table->timestamp('completed_at')->nullable(); // RF-46: dasar kelayakan honor
            $table->timestamps();

            $table->unique(['casting_project_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_project_assignments');
    }
};
