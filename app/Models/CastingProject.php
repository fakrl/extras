<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['admin_id', 'nama_produksi', 'client_ph', 'wa_group_link', 'deadline', 'kuota', 'is_urgent', 'status'])]
class CastingProject extends Model
{
    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'is_urgent' => 'boolean',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(CastingProjectClass::class);
    }

    public function shootingDates(): HasMany
    {
        return $this->hasMany(EventShootingDate::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ProjectApplication::class);
    }

    public function adminAssignments(): HasMany
    {
        return $this->hasMany(AdminProjectAssignment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * RF-13/RF-22: extras yang punya keterlibatan aktif (Deal/Lolos ke atas,
     * belum selesai/batal) di proyek LAIN yang tanggal shooting-nya overlap
     * dengan proyek ini. Dipakai buat soft-warning, bukan blocking.
     */
    public function extrasIdsWithConflictingSchedule(): array
    {
        $tanggalProyekIni = $this->shootingDates()->pluck('tanggal');

        return ProjectApplication::query()
            ->whereIn('status_partisipasi', ProjectApplication::STATUS_AKTIF)
            ->where('casting_project_id', '!=', $this->id)
            ->whereHas('castingProject.shootingDates', function ($q) use ($tanggalProyekIni) {
                $q->whereIn('tanggal', $tanggalProyekIni);
            })
            ->pluck('extras_id')
            ->unique()
            ->all();
    }
}
