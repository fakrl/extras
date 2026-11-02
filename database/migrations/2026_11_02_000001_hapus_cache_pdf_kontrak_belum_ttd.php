<?php

use App\Models\Contract;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

// Audit item 3: kolom Grade dihapus dari template PDF kontrak. PDF kontrak
// yang BELUM ditandatangani lengkap cuma cache, aman diregenerasi ulang
// (otomatis saat diakses lagi, lihat ProjectApplication::renderKontrakPdf()
// & ContractController::downloadPdf()). Kontrak yang SUDAH signed_at tidak
// disentuh sama sekali - dokumen legal final, bukan cache.
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
