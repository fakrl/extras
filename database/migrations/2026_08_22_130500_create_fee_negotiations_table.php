<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // RF-16 s/d RF-20: negosiasi fee ala InDrive, multi-round, tercatat.
        // Tabel ini adalah jejak audit tiap ronde tawar-menawar — jangan
        // pernah di-update/delete, hanya insert (append-only), karena inilah
        // "catatan kesepakatan yang tidak bisa dibantah" yang jadi value prop inti.
        Schema::create('fee_negotiations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_application_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('round');
            $table->enum('diajukan_oleh', ['admin', 'extras']);
            $table->decimal('nominal', 12, 2);
            $table->enum('aksi', ['tawar', 'counter', 'terima', 'tolak']);
            $table->timestamps();

            $table->index(['project_application_id', 'round']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_negotiations');
    }
};
