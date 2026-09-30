<?php

namespace App\Models;

use Database\Factories\CastingProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

// 'share_token' TETAP masuk $fillable, tapi proteksinya bukan dari sini,
// sama pola dengan ExtrasProfile::foto_profil_path: cuma whitelist teknis,
// tidak ada route/controller yang nerima 'share_token' mentah dari request
// user. Satu-satunya jalur yang mengisi field ini adalah
// Admin\CastingProjectController::store() lewat Str::random(32) literal.
#[Fillable([
    'admin_id', 'nama_produksi', 'poster_path',
    'share_token', 'link_grup', 'deadline', 'kuota',
    'is_urgent', 'status', 'client_request_status', 'brief_catatan', 'alasan_tolak', 'client_id',
    'tampil_portofolio', 'portofolio_judul', 'portofolio_jenis', 'portofolio_tahun', 'tampilkan_nama_client',
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
            'tampil_portofolio' => 'boolean',
            'portofolio_tahun' => 'integer',
            'tampilkan_nama_client' => 'boolean',
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

    /** BM.2: nama Client/PH dari akun Client (pengganti kolom client_ph). */
    public function namaClient(): string
    {
        return $this->client?->nama_perusahaan ?: ($this->client?->name ?? '-');
    }

    /** BN.1: JBTB-{tahun dibuat}-{id 3 digit}, tanpa kolom. */
    protected function kodeProyek(): Attribute
    {
        return Attribute::get(fn () => sprintf('JBTB-%d-%03d', ($this->created_at ?? now())->year, $this->id));
    }

    public function namaKode(): string
    {
        return "{$this->nama_produksi} ({$this->kode_proyek})";
    }

    /** BN.1: "JBTB-2026-012" / "2026-012" / "012" -> filter id (+ tahun); null kalau bukan pola kode. */
    public static function parseKode(string $cari): ?array
    {
        if (! preg_match('/^(?:JBTB-?)?(?:(\d{4})-)?(\d{1,6})$/i', trim($cari), $m)) {
            return null;
        }

        return ['tahun' => $m[1] ? (int) $m[1] : null, 'id' => (int) $m[2]];
    }

    public function scopeCariKode($query, string $cari)
    {
        $kode = self::parseKode($cari);

        return $kode
            ? $query->whereKey($kode['id'])->when($kode['tahun'], fn ($q, $t) => $q->whereYear('casting_projects.created_at', $t))
            : $query->whereRaw('1 = 0');
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

    /** BK.4: dipanggil Admin/Client setelah menambah tanggal shooting; cuma tanggal yang baru dicek. */
    public function kabariBentrokJadwal(iterable $tanggalBaru): void
    {
        $tanggal = collect($tanggalBaru)->map(fn ($t) => Carbon::parse($t)->toDateString())->unique()->values();
        if ($tanggal->isEmpty()) {
            return;
        }

        $this->applications()
            ->whereIn('status_partisipasi', [...ProjectApplication::STATUS_PROSES, ...ProjectApplication::STATUS_PASTI])
            ->with('extras', 'castingProject.shootingDates')
            ->get()
            ->each(fn ($app) => $app->kabariBentrokPasti($tanggal, true));
    }

    public function adminAssignments(): HasMany
    {
        return $this->hasMany(AdminProjectAssignment::class);
    }

    /** BM.1: 1 proyek = 1 akun Client; satu-satunya cek akses Client ke proyek. */
    public function milikClient(User $user): bool
    {
        return $user->isClient() && (int) $this->client_id === $user->id;
    }

    public function scopeMilikClient($query, User $user)
    {
        return $query->where('casting_projects.client_id', $user->id);
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

    /** BE.4: punya peran → penuh kalau SEMUA peran penuh; tanpa peran → kuota level-proyek (RF-56). */
    public function kuotaPenuh(): bool
    {
        $classes = $this->relationLoaded('classes') && $this->classes->every(fn ($c) => array_key_exists('terisi', $c->getAttributes()))
            ? $this->classes
            : $this->classes()->withTerisi()->get();

        if ($classes->isNotEmpty()) {
            return $classes->every(fn ($c) => $c->sisaKuota() === 0);
        }

        return $this->applications()->whereNotIn('status_partisipasi', ['ditolak', 'dibatalkan'])->count() >= $this->kuota;
    }

    /**
     * RF-56: satu-satunya gerbang "masih bisa didaftarin" dipakai
     * PublicEventController, dan return-to-intent di ProfileController/
     * LoginController, supaya definisinya konsisten di mana pun dicek.
     */
    /** RF-11: lowongan yang masih menerima pendaftaran, deadline terdekat dulu. */
    public static function lowonganTerbuka(): Collection
    {
        return static::where('status', 'dibuka')
            ->with('client:id,name,nama_perusahaan')
            ->withCount(['applications as terisi' => fn ($q) => $q->whereIn('status_partisipasi', ['lolos', 'kontrak_ditandatangani', 'selesai_produksi'])])
            ->with(['classes' => fn ($q) => $q->withTerisi()->with('categories:id,nama'), 'shootingDates'])
            ->orderBy('deadline')
            ->get()
            ->filter(fn ($p) => $p->menerimaPendaftaran())
            ->values();
    }

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

    /** BH.3: portofolio beranda cuma untuk proyek yang tahapnya selesai. */
    public function bisaPortofolio(): bool
    {
        return $this->tahap() === 'selesai';
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
