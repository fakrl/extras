<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['project_application_id', 'event_shooting_date_id', 'status', 'dicatat_oleh', 'catatan', 'foto_path', 'status_validasi', 'divalidasi_oleh', 'divalidasi_at'])]
class Attendance extends Model
{
    // DI.1: satu sumber definisi "hadir" (status + sudah tervalidasi) - dipakai KorlapRingkasan::rekap()
    // (in-memory, lewat hadirTervalidasi()) & dashboard SA (query, lewat scopeHadirValidasi()). Jangan duplikasi kondisi ini.
    public function scopeHadirValidasi(Builder $q): Builder
    {
        return $q->where('status', 'hadir')->where('status_validasi', 'tervalidasi');
    }

    public function hadirTervalidasi(): bool
    {
        return $this->status === 'hadir' && $this->status_validasi === 'tervalidasi';
    }

    protected function casts(): array
    {
        return ['divalidasi_at' => 'datetime'];
    }

    public function projectApplication(): BelongsTo
    {
        return $this->belongsTo(ProjectApplication::class);
    }

    public function eventShootingDate(): BelongsTo
    {
        return $this->belongsTo(EventShootingDate::class);
    }

    public function dicatatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    public function divalidasiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'divalidasi_oleh');
    }
}
