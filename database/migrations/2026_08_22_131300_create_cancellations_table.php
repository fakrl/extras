<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_application_id')->constrained()->cascadeOnDelete();
            $table->enum('dibatalkan_oleh', ['admin', 'extras']);
            $table->text('alasan'); // RF-33
            $table->boolean('is_mendadak'); // aturan H-2, RF-34
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancellations');
    }
};
