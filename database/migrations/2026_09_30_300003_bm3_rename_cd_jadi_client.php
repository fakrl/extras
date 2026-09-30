<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// SPEC BM.3: sisa istilah "cd" (Casting Director) → "client".
return new class extends Migration
{
    private const STATUS = ['diajukan', 'direview_admin', 'nego_fee', 'deal', 'diajukan_ke_cd', 'lolos', 'ditolak', 'kontrak_ditandatangani', 'selesai_produksi', 'dibatalkan'];

    public function up(): void
    {
        Schema::rename('cd_reviews', 'client_reviews');
        Schema::table('client_reviews', function (Blueprint $table) {
            $table->renameColumn('cd_id', 'client_id');
            $table->renameColumn('grade_cd', 'grade_client');
        });
        Schema::table('invoices', fn (Blueprint $table) => $table->renameColumn('ttd_cd_signature_path', 'ttd_client_signature_path'));

        $this->gantiStatus('diajukan_ke_cd', 'diajukan_ke_client');
    }

    public function down(): void
    {
        $this->gantiStatus('diajukan_ke_client', 'diajukan_ke_cd');

        Schema::table('invoices', fn (Blueprint $table) => $table->renameColumn('ttd_client_signature_path', 'ttd_cd_signature_path'));
        Schema::table('client_reviews', function (Blueprint $table) {
            $table->renameColumn('client_id', 'cd_id');
            $table->renameColumn('grade_client', 'grade_cd');
        });
        Schema::rename('client_reviews', 'cd_reviews');
    }

    /** Enum MySQL: tambah nilai baru → pindah data → buang nilai lama. */
    private function gantiStatus(string $lama, string $baru): void
    {
        $sekarang = array_map(fn ($s) => $s === 'diajukan_ke_cd' ? $lama : $s, self::STATUS);
        $akhir = array_map(fn ($s) => $s === $lama ? $baru : $s, $sekarang);

        $this->enum(array_values(array_unique([...$sekarang, $baru])));
        DB::table('project_applications')->where('status_partisipasi', $lama)->update(['status_partisipasi' => $baru]);
        $this->enum($akhir);
    }

    private function enum(array $nilai): void
    {
        Schema::table('project_applications', fn (Blueprint $table) => $table->enum('status_partisipasi', $nilai)->default('diajukan')->change());
    }
};
