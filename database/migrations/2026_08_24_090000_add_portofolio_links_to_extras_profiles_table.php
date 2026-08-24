<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RF-14 & CLAUDE.md §5 (Tembok Visibilitas): tautan tambahan (sosmed,
     * portofolio, dsb) HANYA dilihat Extras sendiri & Admin -- TIDAK PERNAH
     * dikirim ke view/response milik Casting Director. Ke CD, profil Extras
     * cuma nampilin alias + foto/video (data dasar), tanpa satu pun tautan.
     *
     * Disimpan sebagai satu kolom JSON (array of {label, url}) supaya
     * jumlahnya bebas/fleksibel (tombol "+" di form), bukan kolom fix
     * per-platform (instagram/tiktok/dst).
     */
    public function up(): void
    {
        Schema::table('extras_profiles', function (Blueprint $table) {
            $table->json('tautan_tambahan')->nullable()->after('bahasa');
        });
    }

    public function down(): void
    {
        Schema::table('extras_profiles', function (Blueprint $table) {
            $table->dropColumn('tautan_tambahan');
        });
    }
};
