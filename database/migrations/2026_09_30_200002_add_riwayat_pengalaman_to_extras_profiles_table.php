<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// SPEC BJ.3: pengalaman jadi daftar; teks lama jadi entri pertama (idempoten). Kolom `pengalaman` sengaja tidak di-drop.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('extras_profiles', 'riwayat_pengalaman')) {
            Schema::table('extras_profiles', function (Blueprint $table) {
                $table->json('riwayat_pengalaman')->nullable()->after('pengalaman');
            });
        }

        DB::table('extras_profiles')->whereNull('riwayat_pengalaman')->whereNotNull('pengalaman')->where('pengalaman', '!=', '')
            ->eachById(fn ($p) => DB::table('extras_profiles')->where('id', $p->id)->update([
                'riwayat_pengalaman' => json_encode([['judul' => trim($p->pengalaman), 'keterangan' => null, 'tahun' => null]]),
            ]));
    }

    public function down(): void
    {
        Schema::table('extras_profiles', function (Blueprint $table) {
            $table->dropColumn('riwayat_pengalaman');
        });
    }
};
