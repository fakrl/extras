<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// 'urutan' & 'path' SENGAJA boleh mass-assignment DI SINI karena proteksi
// yang sebenarnya bukan di $fillable, tapi di jalur aksesnya: satu-satunya
// cara mengisi baris ini adalah lewat ExtrasProfile::simpanFotoTambahan(),
// yang cuma dipanggil dari ProfileController::uploadFotoTambahan() —
// endpoint itu sudah validasi file (UploadedFile asli, jenis & ukuran)
// dan otorisasi (hanya pemilik profil login yang bisa memanggilnya).
// Tidak ada request user yang bisa mengisi tabel ini secara langsung.
class ExtrasPhoto extends Model
{
    protected $fillable = ['urutan', 'path'];

    public function extrasProfile(): BelongsTo
    {
        return $this->belongsTo(ExtrasProfile::class);
    }
}
