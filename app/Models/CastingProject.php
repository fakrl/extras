<?php

namespace App\Models;

use Database\Factories\CastingProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// 'share_token' TETAP masuk $fillable, tapi proteksinya bukan dari sini —
// sama pola dengan ExtrasProfile::foto_profil_path: cuma whitelist teknis,
// tidak ada route/controller yang nerima 'share_token' mentah dari request
// user. Satu-satunya jalur yang mengisi field ini adalah
// Admin\CastingProjectController::store() lewat Str::random(32) literal.
#[Fillable(['admin_id', 'nama_produksi', 'client_ph', 'share_token', 'wa_group_link', 'deadline', 'kuota', 'is_urgent', 'status'])]
class CastingProject extends Model
{
    /** @use HasFactory<CastingProjectFactory> */
    use HasFactory;

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

    public function cdAssignments(): HasMany
    {
        return $this->hasMany(CdProjectAssignment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * RF-56: definisi "kuota penuh" dipakai konsisten di seluruh fitur link
     * publik (gerbang B4/B5) — total pendaftar vs kuota level-proyek, BUKAN
     * kuota_kelas per kelas (konsep berbeda, breakdown internal CD/Admin).
     */
    public function kuotaPenuh(): bool
    {
        return $this->applications()->count() >= $this->kuota;
    }

    /**
     * RF-56: satu-satunya gerbang "masih bisa didaftarin" dipakai
     * PublicEventController, dan return-to-intent di ProfileController/
     * LoginController — supaya definisinya konsisten di mana pun dicek.
     */
    public function menerimaPendaftaran(): bool
    {
        return $this->status === 'dibuka'
            && ! $this->deadline->isBefore(today())
            && ! $this->kuotaPenuh();
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
