<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_shooting_date_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['hadir', 'tidak_hadir']);
            $table->foreignId('dicatat_oleh')->constrained('users'); // SPEC.md Bagian F
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['project_application_id', 'event_shooting_date_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
