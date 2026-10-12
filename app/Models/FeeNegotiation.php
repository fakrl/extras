<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Whitelist teknis cuma 4 kolom yang dipakai ProjectApplication
// (ajukanFeeAwal/counterFee/terimaFee/tolakNegosiasi), proteksinya BUKAN dari
// sini, tapi karena tidak ada controller yang panggil create()/FeeNegotiation
// langsung (dicek 28 Agu 2026, cuma ProjectApplication yang insert, semua
// lewat scalar param typed, bukan raw request). Tanpa Fillable ini, keempat
// method di atas selalu throw MassAssignmentException, bug nyata sejak
// Sprint 1, baru ketahuan sekarang karena belum ada testing end-to-end.
#[Fillable(['round', 'diajukan_oleh', 'nominal', 'aksi', 'catatan'])]
class FeeNegotiation extends Model
{
    const LABELS = ['tawar' => 'Penawaran', 'counter' => 'Counter', 'terima' => 'Diterima', 'tolak' => 'Ditolak'];

    const BADGES = ['tawar' => 'badge-pending', 'counter' => 'badge-pending', 'terima' => 'badge-aktif', 'tolak' => 'badge-tolak'];

    public function label(): string
    {
        return self::LABELS[$this->aksi] ?? ucfirst($this->aksi);
    }

    public function badgeClass(): string
    {
        return self::BADGES[$this->aksi] ?? 'badge-netral';
    }

    public function projectApplication(): BelongsTo
    {
        return $this->belongsTo(ProjectApplication::class);
    }
}
