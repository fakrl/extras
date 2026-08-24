<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('casting_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('extras_id')->constrained('extras_profiles')->cascadeOnDelete();

            // RF-24: dua jalur status terpisah (partisipasi vs pembayaran).
            // Tabel ini cuma nyimpen status partisipasi — status pembayaran
            // ada di tabel `payments` sendiri. JANGAN digabung jadi satu
            // state machine (lihat CLAUDE.md §6).
            $table->enum('status_partisipasi', [
                'diajukan',
                'direview_admin',
                'nego_fee',
                'deal',
                'diajukan_ke_cd',
                'direview_cd',
                'lolos',
                'ditolak',
                'kontrak_ditandatangani',
                'selesai_produksi',
                'dibatalkan',
            ])->default('diajukan');

            $table->enum('grade', ['A', 'B', 'C'])->nullable(); // RF-15, independen dari fee
            $table->decimal('fee_final', 12, 2)->nullable(); // diisi begitu status jadi 'deal'

            // RF-13/RF-22: flag non-blocking, ditandai sistem saat apply/present
            $table->boolean('bentrok_jadwal_flag')->default(false);

            $table->timestamps();

            $table->unique(['casting_project_id', 'extras_id']); // satu extras cuma 1 aplikasi per proyek
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_applications');
    }
};
