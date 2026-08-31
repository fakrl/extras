<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cd_project_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('casting_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cd_user_id')->constrained('users');
            $table->timestamps();

            $table->unique(['casting_project_id', 'cd_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cd_project_assignments');
    }
};
