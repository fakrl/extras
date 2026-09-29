<?php

namespace App\Models;

use Database\Factories\CastingProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

// 'share_token' TETAP masuk $fillable, tapi proteksinya bukan dari sini,
// sama pola dengan ExtrasProfile::foto_profil_path: cuma whitelist teknis,
// tidak ada route/controller yang nerima 'share_token' mentah dari request
// user. Satu-satunya jalur yang mengisi field ini adalah
// Admin\CastingProjectController::store() lewat Str::random(32) literal.
#[Fillable([
    'admin_id', 'nama_produksi', 'client_ph', 'poster_path', 'cover_path',
    'share_token', 'wa_group_link', 'link_grup', 'deadline', 'kuota',
    'is_urgent', 'status', 'client_request_status', 'diajukan_oleh_client_id', 'brief_catatan', 'alasan_tolak', 'client_id',
])]
class CastingProject extends Model
{
    /** @use HasFactory<CastingProjectFactory> */
    use HasFactory;

    const LABELS = [
        'dibuka' => 'Dibuka',
        'ditutup' => 'Ditutup',
    ];

    const BADGES = [
        'dibuka' => 'badge-aktif',
        'ditutup' => 'badge-netral',
    ];

    public function label(): string
    {
        return self::LABELS[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function badgeClass(): string
    {
        return self::BADGES[$this->status] ?? 'badge-netral';
    }

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

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(ProjectExpense::class)->orderBy('tanggal');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ProjectAttachment::class)->latest();
    }

    public function classes(): HasMany
    {
        return $this->hasMany(CastingProjectClass::class);
    }

    public function shootingDates(): HasMany
    {
        return $this->hasMany(EventShootingDate::class)->orderBy('tanggal');
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

    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(Payment::class, ProjectApplication::class);
    }

    public function payrolls(): HasManyThrough
    {
        return $this->hasManyThrough(StaffPayroll::class, AdminProjectAssignment::class);
    }

    /**
     * RF-56: definisi "kuota penuh" dipakai konsisten di seluruh fitur link
     * publik (gerbang B4/B5), total pendaftar vs kuota level-proyek, BUKAN
     * kuota_kelas per kelas (konsep berbeda, breakdown internal CD/Admin).
     */
    public function kuotaPenuh(): bool
    {
        return $this->applications()->whereNotIn('status_partisipasi', ['ditolak', 'dibatalkan'])->count() >= $this->kuota;
    }

    /**
     * RF-56: satu-satunya gerbang "masih bisa didaftarin" dipakai
     * PublicEventController, dan return-to-intent di ProfileController/
     * LoginController, supaya definisinya konsisten di mana pun dicek.
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

    const TAHAP = [
        'menunggu_acc' => 'Menunggu ACC',
        'mendatang' => 'Mendatang',
        'berjalan' => 'Berjalan',
        'selesai' => 'Selesai',
    ];

    const TAHAP_BADGES = [
        'menunggu_acc' => 'badge-pending',
        'mendatang' => 'badge-info',
        'berjalan' => 'badge-aktif',
        'selesai' => 'badge-netral',
    ];

    /**
     * BD.2: tahap proyek dari tanggal shooting (bukan status lowongan dibuka/ditutup).
     * Menunggu ACC = pengajuan Client belum di-ACC. Sisanya hanya proyek disetujui:
     * Berjalan = shooting pertama <= hari ini <= shooting terakhir;
     * Mendatang = shooting pertama > hari ini, atau belum ada jadwal & lowongan dibuka;
     * Selesai = shooting terakhir < hari ini, atau belum ada jadwal & lowongan ditutup.
     */
    public function scopeDiTahap($query, string $tahap)
    {
        if ($tahap === 'menunggu_acc') {
            return $query->where('client_request_status', 'menunggu_acc');
        }

        $query->where('client_request_status', 'disetujui');
        $sebelum = fn ($q) => $q->whereDate('tanggal', '<=', today());
        $sesudah = fn ($q) => $q->whereDate('tanggal', '>=', today());

        return match ($tahap) {
            'berjalan' => $query->whereHas('shootingDates', $sebelum)->whereHas('shootingDates', $sesudah),
            'mendatang' => $query->whereDoesntHave('shootingDates', $sebelum)
                ->where(fn ($q) => $q->has('shootingDates')->orWhere('status', 'dibuka')),
            'selesai' => $query->where(fn ($q) => $q
                ->where(fn ($w) => $w->has('shootingDates')->whereDoesntHave('shootingDates', $sesudah))
                ->orWhere(fn ($w) => $w->doesntHave('shootingDates')->where('status', 'ditutup'))),
            default => $query,
        };
    }

    /**
     * BE.2: proyek yang punya tanggal shooting dalam [from, to]. Dipakai dashboard SA & daftar proyek.
     */
    public function scopeShootingDalam($query, $from, $to)
    {
        return $query->whereHas('shootingDates', fn ($q) => $q->whereDate('tanggal', '>=', $from)->whereDate('tanggal', '<=', $to));
    }

    public function tahap(): ?string
    {
        if ($this->client_request_status === 'menunggu_acc') {
            return 'menunggu_acc';
        }
        if ($this->client_request_status !== 'disetujui') {
            return null;
        }
        $awal = $this->shootingDates->min('tanggal');
        $akhir = $this->shootingDates->max('tanggal');

        return match (true) {
            ! $awal => $this->status === 'dibuka' ? 'mendatang' : 'selesai',
            $awal->isAfter(today()) => 'mendatang',
            $akhir->isBefore(today()) => 'selesai',
            default => 'berjalan',
        };
    }

    public function rentangShooting(): string
    {
        $awal = $this->shootingDates->min('tanggal');
        $akhir = $this->shootingDates->max('tanggal');

        return match (true) {
            ! $awal => '-',
            $awal->isSameDay($akhir) => $awal->translatedFormat('d M Y'),
            default => $awal->translatedFormat('d M').' – '.$akhir->translatedFormat('d M Y'),
        };
    }

    public function diajukanOlehClient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diajukan_oleh_client_id');
    }

    public function isUrgent(): bool
    {
        if ($this->is_urgent) {
            return true;
        }

        // Otomatis H-3 jika shooting terdekat <= 3 hari dan kuota belum penuh
        $closestShooting = $this->shootingDates()->where('tanggal', '>=', today())->min('tanggal');
        if ($closestShooting) {
            $daysLeft = (int) today()->diffInDays($closestShooting, false);
            if ($daysLeft >= 0 && $daysLeft <= 3 && ! $this->kuotaPenuh()) {
                return true;
            }
        }

        return false;
    }
}
