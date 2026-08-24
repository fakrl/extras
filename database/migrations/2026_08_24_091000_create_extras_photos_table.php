<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RF-06 (perluasan): selain 1 foto profil utama (extras_profiles.
     * foto_profil_path), Extras juga bisa upload sampai 4 foto tambahan
     * (foto model/visual sisi lain) untuk dinilai Admin -- sesuai visi
     * prototype (grid foto), tapi tanpa label/kategori kaku (polos, 4 slot
     * bebas yang bisa diganti-ganti).
     *
     * 'urutan' (1-4) berfungsi sebagai slot: unique bareng extras_profile_id
     * supaya upload baru ke slot yang sama REPLACE foto lama, bukan nambah
     * baris baru -- konsisten dengan keputusan "4 slot yang bisa diganti".
     */
    public function up(): void
    {
        Schema::create('extras_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extras_profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('urutan'); // slot 1-4
            $table->string('path');
            $table->timestamps();

            $table->unique(['extras_profile_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extras_photos');
    }
};
