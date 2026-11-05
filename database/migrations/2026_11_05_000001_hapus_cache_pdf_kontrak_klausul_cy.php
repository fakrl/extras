<?php

use App\Models\Contract;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

// SPEC.md Bagian CY: template kontrak nambah klausul kuasa & potongan 25%.
// Kontrak yang pdf-nya sempat di-cache SEJAK migrasi 2026_11_02_000001
// (jadi belum kena cache-bust itu) tapi masih pakai template lama (belum
// ada klausul CY) perlu dibuang cache-nya juga, pola identik: cuma hapus
// file cache, bukan baris DB, cuma yang BELUM signed_at.
return new class extends Migration
{
    public function up(): void
    {
        Contract::whereNull('signed_at')
            ->whereNotNull('pdf_path')
            ->lazyById()
            ->each(fn (Contract $contract) => Storage::disk('local')->delete($contract->pdf_path));
    }

    public function down(): void {}
};
