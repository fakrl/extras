<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu row per tanggal shooting — mendukung tanggal jamak yang tidak
        // harus berurutan (temuan data riil: proyek besar bisa 9-13 hari
        // shooting, kemungkinan tidak berurutan). Ini yang membuat deteksi
        // bentrok jadwal (RF-13, RF-22) jadi simple overlap-check per tanggal,
        // bukan constraint solver rumit atas rentang start/end.
        Schema::create('event_shooting_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('casting_project_id')->constrained()->cascadeOnDelete();
            $table->date('tanggal');
            $table->timestamps();

            $table->index(['casting_project_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_shooting_dates');
    }
};
