<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cd_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cd_id')->constrained('users');
            $table->enum('keputusan', ['approve', 'reject']);
            // RF-23: approve/reject massal — dilacak sebagai satu aksi
            $table->uuid('bulk_batch_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cd_reviews');
    }
};
