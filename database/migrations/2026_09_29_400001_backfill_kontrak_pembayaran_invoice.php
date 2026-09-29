<?php

use App\Models\ProjectApplication;
use Illuminate\Database\Migrations\Migration;

// BE.1: record yang dulu baru dibuat saat halaman GET dibuka. Idempoten, tanpa notifikasi.
return new class extends Migration
{
    public function up(): void
    {
        ProjectApplication::whereIn('status_partisipasi', ProjectApplication::STATUS_LOLOS_KE_ATAS)
            ->with('extras', 'castingProject')
            ->lazyById()
            ->each(fn (ProjectApplication $application) => $application->siapkanKontrakDanPembayaran(kirimNotifikasi: false));
    }

    public function down(): void {}
};
