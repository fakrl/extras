<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DA.2: consent privasi baru (dulu cuma divalidasi, tidak disimpan). Backfill
     * extras existing pakai created_at sendiri supaya tidak keblokir middleware baru
     * pasca-deploy. hasColumn guard: migration ini juga dipanggil ulang manual di test
     * untuk verifikasi backfill (lihat PrivasiConsentTest).
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'privasi_disetujui_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('privasi_disetujui_at')->nullable()->after('wajib_ganti_password');
            });
        }

        DB::table('users')->where('role', 'extras')->whereNull('privasi_disetujui_at')
            ->update(['privasi_disetujui_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('privasi_disetujui_at');
        });
    }
};
