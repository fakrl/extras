<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('casting_project_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('casting_project_id')->constrained()->cascadeOnDelete();
            $table->string('nama_kelas'); // misal "Ibu-ibu 29-50th"
            $table->json('kriteria')->nullable();
            $table->decimal('budget_client', 12, 2); // dasar penawaran fee awal, RF-16
            $table->unsignedInteger('kuota_kelas');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('casting_project_classes');
    }
};
