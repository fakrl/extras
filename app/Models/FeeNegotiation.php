<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Whitelist teknis cuma 4 kolom yang dipakai ProjectApplication
// (ajukanFeeAwal/counterFee/terimaFee/tolakNegosiasi) — proteksinya BUKAN dari
// sini, tapi karena tidak ada controller yang panggil create()/FeeNegotiation
// langsung (dicek 28 Agu 2026, cuma ProjectApplication yang insert, semua
// lewat scalar param typed, bukan raw request). Tanpa Fillable ini, keempat
// method di atas selalu throw MassAssignmentException — bug nyata sejak
// Sprint 1, baru ketahuan sekarang karena belum ada testing end-to-end.
#[Fillable(['round', 'diajukan_oleh', 'nominal', 'aksi'])]
class FeeNegotiation extends Model
{
    public function projectApplication(): BelongsTo
    {
        return $this->belongsTo(ProjectApplication::class);
    }
}
