<?php

namespace App\Models;

use App\Mail\HasilSeleksiMail;
use App\Mail\KonfirmasiFeeMail;
use App\Mail\KontrakSiapTtdMail;
use App\Notifications\InAppNotification;
use App\Services\PdfGeneratorService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[Fillable([
    'casting_project_id', 'extras_id', 'casting_project_class_id', 'status_partisipasi',
    'grade', 'fee_final', 'bentrok_jadwal_flag', 'alasan_tolak',
    'karakter_override', 'scene_override', 'jam_callingan_override', 'tipe_continuity_override',
])]
class ProjectApplication extends Model
{
    const STATUS_AKTIF = ['deal', 'diajukan_ke_client', 'lolos', 'kontrak_ditandatangani'];

    const STATUS_LOLOS_KE_ATAS = ['lolos', 'kontrak_ditandatangani', 'selesai_produksi'];

    /** BK.4: bentrok dengan pendaftaran `pasti` memblokir; dengan `proses` cuma peringatan. */
    const STATUS_PASTI = ['lolos', 'kontrak_ditandatangani'];

    const STATUS_PROSES = ['diajukan', 'direview_admin', 'nego_fee', 'deal', 'diajukan_ke_client'];

    const LABELS = [
        'diajukan' => 'Diajukan',
        'direview_admin' => 'Direview Admin',
        'nego_fee' => 'Nego Fee',
        'deal' => 'Deal',
        'diajukan_ke_client' => 'Diajukan ke Client',
        'lolos' => 'Lolos',
        'ditolak' => 'Ditolak',
        'kontrak_ditandatangani' => 'Kontrak Ditandatangani',
        'selesai_produksi' => 'Selesai Produksi',
        'dibatalkan' => 'Dibatalkan',
    ];

    const BADGES = [
        'diajukan' => 'badge-netral',
        'direview_admin' => 'badge-info',
        'nego_fee' => 'badge-pending',
        'deal' => 'badge-aktif',
        'diajukan_ke_client' => 'badge-info',
        'lolos' => 'badge-aktif',
        'ditolak' => 'badge-tolak',
        'kontrak_ditandatangani' => 'badge-aktif',
        'selesai_produksi' => 'badge-aktif',
        'dibatalkan' => 'badge-tolak',
    ];

    public function label(): string
    {
        return self::LABELS[$this->status_partisipasi] ?? ucfirst(str_replace('_', ' ', $this->status_partisipasi));
    }

    public function badgeClass(): string
    {
        return self::BADGES[$this->status_partisipasi] ?? 'badge-netral';
    }

    public function isPasti(): bool
    {
        return in_array($this->status_partisipasi, self::STATUS_PASTI, true);
    }

    /** BK.4: tanggal shooting proyek, format Y-m-d. */
    public function tanggalShooting(): Collection
    {
        return $this->castingProject->shootingDates->toBase()->map(fn ($d) => $d->tanggal->toDateString());
    }

    /** BK.4: dari $apps, yang proses/pasti & beririsan dengan $tanggal; irisannya di relasi `tanggalBentrok`. */
    public static function saringBentrok(Collection $apps, Collection $tanggal): Collection
    {
        return $apps->filter(fn ($a) => in_array($a->status_partisipasi, [...self::STATUS_PROSES, ...self::STATUS_PASTI], true))
            ->each(fn ($a) => $a->setRelation('tanggalBentrok', $a->tanggalShooting()->intersect($tanggal)->values()))
            ->filter(fn ($a) => $a->tanggalBentrok->isNotEmpty())
            ->values();
    }

    /**
     * BK.4: bentrok antara pendaftaran ini dan pendaftaran lain yang salah satunya sudah pasti
     * (jadi lolos / jadwal berubah): flag di dua-duanya, kabari Extras + Admin proyek lain
     * (+ Admin proyek ini kalau $adminProyekIni). Dikirim setelah commit.
     */
    public function kabariBentrokPasti(?Collection $tanggal = null, bool $adminProyekIni = false): Collection
    {
        $lain = $this->extras->pendaftaranBentrok($tanggal ?? $this->tanggalShooting(), $this->id)
            ->filter(fn ($b) => $this->isPasti() || $b->isPasti())
            ->values();

        if ($lain->isEmpty()) {
            return $lain;
        }

        self::whereKey([$this->id, ...$lain->modelKeys()])->update(['bentrok_jadwal_flag' => true]);
        $this->bentrok_jadwal_flag = true;

        DB::afterCommit(function () use ($lain, $adminProyekIni) {
            $this->loadMissing('extras.user', 'castingProject.admin');
            foreach ($lain as $b) {
                [$pasti, $pilih] = $b->isPasti() ? [$b, $this] : [$this, $b];
                $namaPasti = $pasti->castingProject->namaKode();
                $namaPilih = $pilih->castingProject->namaKode();
                $tgl = $b->tanggalBentrok->map(fn ($t) => Carbon::parse($t)->translatedFormat('d M Y'))->join(', ');

                $kirim = [[$this->extras->user, 'Jadwal Bentrok', "{$namaPasti} sudah pasti dan tanggalnya sama dengan {$namaPilih} ({$tgl}). Pilih salah satu.", route('extras.dashboard').'#pendaftaran-'.$pilih->id]];
                foreach (array_filter([$b, $adminProyekIni ? $this : null]) as $app) {
                    if ($app->castingProject->admin && ($app === $b || $app->castingProject->admin_id !== $b->castingProject->admin_id)) {
                        $kirim[] = [$app->castingProject->admin, 'Jadwal Bentrok Kandidat', "{$this->extras->user->name}: {$namaPasti} (sudah pasti) bentrok dengan {$namaPilih} tanggal {$tgl}.", route('admin.projects.applicants', $app->casting_project_id)];
                    }
                }

                foreach ($kirim as [$user, $judul, $pesan, $url]) {
                    try {
                        $user->notify(new InAppNotification($judul, $pesan, $url));
                    } catch (\Throwable) {
                    }
                }
            }
        });

        return $lain;
    }

    public function getKarakterAttribute(): ?string
    {
        return $this->karakter_override ?? $this->castingProjectClass?->nama_kelas;
    }

    public function getKeteranganSceneAttribute(): ?string
    {
        return $this->scene_override ?? $this->castingProjectClass?->keterangan_scene;
    }

    public function getJamCallinganAttribute(): ?string
    {
        // substr: MySQL balikin kolom TIME sebagai HH:MM:SS
        $jam = $this->jam_callingan_override ?? $this->castingProjectClass?->jam_callingan;

        return $jam ? substr($jam, 0, 5) : null;
    }

    public function getTipeContinuityAttribute(): ?string
    {
        return $this->tipe_continuity_override ?? $this->castingProjectClass?->tipe_continuity ?? 'free';
    }

    public function getKarakter(): string
    {
        return $this->karakter_override ?? $this->castingProjectClass?->nama_kelas ?? '-';
    }

    public function getScene(): string
    {
        return $this->scene_override ?? $this->castingProjectClass?->keterangan_scene ?? '-';
    }

    public function getJamCallingan(): string
    {
        return $this->jam_callingan ?? '-';
    }

    protected function casts(): array
    {
        return [
            'bentrok_jadwal_flag' => 'boolean',
            'fee_final' => 'decimal:2',
        ];
    }

    public function castingProject(): BelongsTo
    {
        return $this->belongsTo(CastingProject::class);
    }

    public function extras(): BelongsTo
    {
        return $this->belongsTo(ExtrasProfile::class, 'extras_id');
    }

    public function castingProjectClass(): BelongsTo
    {
        return $this->belongsTo(CastingProjectClass::class);
    }

    public function feeNegotiations(): HasMany
    {
        return $this->hasMany(FeeNegotiation::class)->orderBy('round');
    }

    public function contract(): HasOne
    {
        return $this->hasOne(Contract::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function clientReviews(): HasMany
    {
        return $this->hasMany(ClientReview::class);
    }

    public function cancellations(): HasMany
    {
        return $this->hasMany(Cancellation::class);
    }

    public function fieldNotes(): HasMany
    {
        return $this->hasMany(FieldNote::class)->latest();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * BA.3: % tag peran yang dimiliki Extras. Null kalau peran tanpa tag.
     * Eager-load `castingProjectClass.categories` + `extras.categories` di list.
     */
    public function persenCocok(): ?int
    {
        return $this->castingProjectClass?->persenCocok($this->extras?->categories->modelKeys() ?? []);
    }

    /**
     * BA.5: urut persenCocok() desc di SQL biar berlaku lintas halaman paginate.
     * Peran tanpa tag (rasio NULL) jatuh di akhir, MySQL & SQLite sama.
     */
    public function scopeUrutPalingCocok($query)
    {
        return $query->orderByRaw('(select count(*) from casting_project_class_extras_category k
            join extras_category_extras_profile e on e.extras_category_id = k.extras_category_id
            where k.casting_project_class_id = project_applications.casting_project_class_id
            and e.extras_profile_id = project_applications.extras_id) * 1.0
            / nullif((select count(*) from casting_project_class_extras_category k2
            where k2.casting_project_class_id = project_applications.casting_project_class_id), 0) desc');
    }

    /**
     * RF-16: Admin ajukan penawaran fee awal. Ronde 1, selalu dari admin.
     */
    public function ajukanFeeAwal(float $nominal, ?string $catatan = null): FeeNegotiation
    {
        if (! in_array($this->status_partisipasi, ['diajukan', 'direview_admin'], true)) {
            throw new \LogicException('Kandidat ini tidak bisa diajukan fee awal, statusnya sudah bukan Diajukan/Direview Admin.');
        }

        $this->update(['status_partisipasi' => 'nego_fee']);

        $negotiation = $this->feeNegotiations()->create([
            'round' => 1,
            'diajukan_oleh' => 'admin',
            'nominal' => $nominal,
            'aksi' => 'tawar',
            'catatan' => $catatan,
        ]);

        $this->kirimKonfirmasiFee($negotiation, $this->extras->user);

        return $negotiation;
    }

    /**
     * RF-17/RF-18: counter dari salah satu pihak, ronde bertambah, tidak
     * dibatasi jumlah putaran (mekanisme ala InDrive).
     */
    public function counterFee(string $diajukanOleh, float $nominal, ?string $catatan = null): FeeNegotiation
    {
        $roundTerakhir = $this->feeNegotiations()->max('round') ?? 0;

        $negotiation = $this->feeNegotiations()->create([
            'round' => $roundTerakhir + 1,
            'diajukan_oleh' => $diajukanOleh,
            'nominal' => $nominal,
            'aksi' => 'counter',
            'catatan' => $catatan,
        ]);

        $penerima = $diajukanOleh === 'admin' ? $this->extras->user : $this->castingProject->admin;
        $this->kirimKonfirmasiFee($negotiation, $penerima);

        return $negotiation;
    }

    /**
     * RF-20: salah satu pihak terima -> status "Deal", fee terkunci.
     */
    public function terimaFee(string $diterimaOleh, float $nominal, ?string $catatan = null): FeeNegotiation
    {
        $roundTerakhir = $this->feeNegotiations()->max('round') ?? 0;

        $negotiation = $this->feeNegotiations()->create([
            'round' => $roundTerakhir + 1,
            'diajukan_oleh' => $diterimaOleh,
            'nominal' => $nominal,
            'aksi' => 'terima',
            'catatan' => $catatan,
        ]);

        $this->update([
            'status_partisipasi' => 'deal',
            'fee_final' => $nominal,
        ]);

        return $negotiation;
    }

    /**
     * RF-18: admin bisa hentikan proses negosiasi (tolak), bukan cuma extras.
     */
    public function tolakNegosiasi(string $ditolakOleh, ?string $catatan = null): FeeNegotiation
    {
        $roundTerakhir = $this->feeNegotiations()->max('round') ?? 0;

        $negotiation = $this->feeNegotiations()->create([
            'round' => $roundTerakhir + 1,
            'diajukan_oleh' => $ditolakOleh,
            'nominal' => 0,
            'aksi' => 'tolak',
            'catatan' => $catatan,
        ]);

        $this->update(['status_partisipasi' => 'ditolak']);

        return $negotiation;
    }

    /**
     * RF-15 (perluasan): Admin bisa reject kandidat lebih dini, sebelum masuk
     * fase nego fee, kalau jelas tidak sesuai spesifikasi/kriteria tokoh yang
     * dicari. Beda dari tolakNegosiasi() (yang khusus fase nego) dan dari
     * keputusan Client (RF-23, fase review talent). Cuma boleh dipanggil selagi
     * status masih di fase awal, biar tidak bisa "menyalip" kandidat yang
     * sudah masuk nego/diajukan ke Client.
     */
    public function tolakDini(string $alasan): void
    {
        if (! in_array($this->status_partisipasi, ['diajukan', 'direview_admin'], true)) {
            throw new \LogicException('Kandidat ini sudah masuk proses nego/review, tidak bisa direject lewat jalur ini.');
        }

        $this->update([
            'status_partisipasi' => 'ditolak',
            'alasan_tolak' => $alasan,
        ]);

        $this->kirimNotifikasiHasil();
    }

    /**
     * RF-21: hanya kandidat yang fee-nya sudah Deal yang boleh diajukan ke Client.
     * Ini penjaga urutan supaya alur "nego dulu, baru present ke Client" tidak
     * bisa dilewati dari controller mana pun.
     *
     * RF-22: re-cek bentrok jadwal di titik ini juga (bisa saja proyek lain
     * baru Deal setelah aplikasi ini Deal duluan). Non-blocking sama seperti
     * RF-13 di apply(), cuma re-set bentrok_jadwal_flag, tetap lanjut.
     * Return true kalau bentrok, supaya controller bisa kasih warning.
     */
    public function ajukanKeClient(): bool
    {
        if ($this->status_partisipasi !== 'deal') {
            throw new \LogicException('Kandidat hanya bisa diajukan ke Client setelah fee Deal.');
        }

        if (! $this->castingProject->client_id) {
            throw new \LogicException('Proyek ini belum punya akun Client. Pilih Client dulu di halaman edit proyek sebelum mengajukan kandidat.');
        }

        $adaBentrok = $this->extras->pendaftaranBentrok($this->tanggalShooting(), $this->id)->isNotEmpty();

        $this->update([
            'status_partisipasi' => 'diajukan_ke_client',
            'bentrok_jadwal_flag' => $adaBentrok,
        ]);

        return $adaBentrok;
    }

    /**
     * RF-33/34: Admin atau Extras membatalkan aplikasi berstatus Deal, Lolos,
     * atau Kontrak Ditandatangani (diperluas SPEC.md Bagian C, 31 Agu 2026,
     * sebelumnya cuma Deal, bikin RF-08 nyaris mustahil kejadian karena
     * kandidat biasanya sudah lewat Deal saat mendekati tanggal shooting).
     * Tidak termasuk Selesai Produksi (sudah kelar, tidak masuk akal batal)
     * atau status pra-Lolos (belum ada komitmen shooting yang bisa dibatalkan
     * mendadak). Satu klik langsung final (tidak ada approval dua pihak,
     * konfirmasi Fakrul 29 Agu 2026), konsisten dengan pola aksi sepihak lain
     * di sini.
     * RF-08: kalau pembatalan mendadak (< H-2 dari tanggal shooting
     * terdekat proyek ini), cek aturan 3x batal mendadak di ExtrasProfile.
     */
    public function batalkan(string $olehSiapa, string $alasan): Cancellation
    {
        // BK.4: pendaftaran yang masih proses boleh dibatalkan kalau bentrok jadwal.
        $bolehKarenaBentrok = in_array($this->status_partisipasi, self::STATUS_PROSES, true)
            && $this->extras->pendaftaranBentrok($this->tanggalShooting(), $this->id)->isNotEmpty();

        if (! in_array($this->status_partisipasi, ['deal', 'lolos', 'kontrak_ditandatangani'], true) && ! $bolehKarenaBentrok) {
            throw new \LogicException('Hanya aplikasi berstatus Deal, Lolos, atau Kontrak Ditandatangani yang bisa dibatalkan.');
        }

        $tanggalTerdekat = $this->castingProject->shootingDates()
            ->where('tanggal', '>=', now()->toDateString())
            ->min('tanggal');

        // Tidak ada tanggal shooting mendatang yang tercatat -> anggap tidak
        // mendadak (tidak ada risiko jadwal yang bisa dinilai).
        $isMendadak = $tanggalTerdekat && now()->startOfDay()->diffInDays($tanggalTerdekat) < 2;

        $cancellation = $this->cancellations()->create([
            'dibatalkan_oleh' => $olehSiapa,
            'alasan' => $alasan,
            'is_mendadak' => $isMendadak,
        ]);

        $this->update(['status_partisipasi' => 'dibatalkan']);

        if ($this->contract) {
            $this->contract->update(['voided_at' => now()]);

            // Regenerasi PDF arsip supaya watermark "TIDAK BERLAKU" ke-bake
            // di file (halaman show.blade.php cek isVoided() live, tapi file
            // PDF statis nggak bisa update sendiri). Efek samping arsip,
            // BUKAN syarat sukses pembatalan, kalau gagal (render/disk),
            // jangan sampai batalkan() ikut gagal, cancellation & status
            // sudah sah tercatat di atas.
            if ($this->contract->pdf_path) {
                try {
                    app(PdfGeneratorService::class)->generate(
                        'contracts.pdf-template',
                        ['application' => $this],
                        $this->contract->pdf_path
                    );
                } catch (\Throwable $e) {
                    Log::warning('Gagal regenerasi PDF kontrak saat void', [
                        'project_application_id' => $this->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        if ($isMendadak && $olehSiapa === 'extras') {
            $this->extras->recordCancellation();
        }

        return $cancellation;
    }

    /**
     * RF-35: catatan/sanksi lapangan dari Korlap (atau Admin Default sebagai
     * dirinya sendiri, sub-role Korlap bukan satu-satunya penulis). Murni
     * informasional, tidak menyentuh status_partisipasi.
     */
    public function tambahCatatan(User $olehSiapa, string $jenis, string $isi): FieldNote
    {
        return $this->fieldNotes()->create([
            'korlap_id' => $olehSiapa->id,
            'jenis' => $jenis,
            'isi' => $isi,
        ]);
    }

    /**
     * RF-36: notif hasil seleksi (lolos/ditolak) ke Extras, dipicu dari
     * tolakDini() di sini, dan dari Client\ReviewController setelah approve/reject.
     * Email adalah efek samping, bukan syarat sukses aksi utama.
     */
    public function kirimNotifikasiHasil(): void
    {
        $user = $this->extras->user;
        $lolos = $this->status_partisipasi === 'lolos';
        $proyek = $this->castingProject->namaKode();

        $user->kabari(
            $lolos ? 'Selamat, Kamu Lolos!' : 'Hasil Seleksi',
            $lolos ? "Kamu lolos seleksi proyek {$proyek}. Cek sistem untuk info lebih lanjut." : "Mohon maaf, kamu belum lolos seleksi proyek {$proyek} kali ini.",
            route('extras.dashboard'),
            jenis: 'hasil_seleksi',
            email: new HasilSeleksiMail($this),
            wa: $lolos
                ? "Halo {$user->name}, selamat! Kamu LOLOS seleksi untuk proyek {$proyek}. Cek sistem untuk info lebih lanjut."
                : "Halo {$user->name}, mohon maaf, kamu belum lolos seleksi untuk proyek {$proyek} kali ini.",
        );
    }

    /**
     * RF-37: konfirmasi WA begitu Extras berhasil apply, titik ini belum
     * punya notif email sama sekali (WA satu-satunya kanal di sini, bukan
     * pelengkap email seperti 3 event lain).
     */
    public function kirimKonfirmasiApply(): void
    {
        $user = $this->extras->user;
        $proyek = $this->castingProject->namaKode();

        $user->kabari(
            'Pendaftaran Diterima',
            "Pendaftaranmu untuk proyek {$proyek} berhasil diterima. Admin akan segera mereview.",
            route('extras.dashboard').'#pendaftaran-'.$this->id,
            jenis: 'konfirmasi_apply',
            wa: "Halo {$user->name}, pendaftaranmu untuk proyek {$proyek} berhasil diterima. Admin akan segera mereview.",
        );
    }

    /**
     * BE.1: record kontrak/pembayaran/invoice dibuat di transisi status, bukan saat halaman dibuka.
     * Idempoten. Kontrak butuh nama_asli + NIK Extras; kalau belum ada, dibuat saat Extras melengkapi.
     */
    public function siapkanKontrakDanPembayaran(bool $kirimNotifikasi = true): void
    {
        if (! in_array($this->status_partisipasi, self::STATUS_LOLOS_KE_ATAS, true)) {
            return;
        }

        $this->payment()->firstOrCreate([], ['status' => 'belum_dibayar']);
        $this->castingProject->invoices()->firstOrCreate([]);

        if (! $this->extras->nama_asli || ! $this->extras->nik) {
            return;
        }

        $contract = $this->contract()->firstOrCreate([]);
        if (! $contract->wasRecentlyCreated) {
            return;
        }

        try {
            $this->renderKontrakPdf();
        } catch (\Throwable $e) {
            Log::warning('Gagal render PDF kontrak, dirender ulang saat diunduh', [
                'project_application_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }

        if ($kirimNotifikasi) {
            $this->kirimNotifikasiKontrak();
        }
    }

    public function renderKontrakPdf(): void
    {
        $this->load('contract', 'extras', 'castingProject');

        $path = "contracts/pdf/{$this->id}.pdf";
        app(PdfGeneratorService::class)->generate('contracts.pdf-template', ['application' => $this], $path);

        $this->contract->update(['pdf_path' => $path]);
    }

    /**
     * RF-36: notif ke kedua pihak begitu kontrak ter-generate. Efek samping, kontrak sudah tersimpan.
     */
    private function kirimNotifikasiKontrak(): void
    {
        $this->loadMissing('extras.user', 'castingProject.admin');

        $proyek = $this->castingProject->namaKode();
        foreach ([$this->extras->user, $this->castingProject->admin] as $penerima) {
            $penerima->kabari(
                'Kontrak Siap Ditandatangani',
                "Kontrak proyek {$proyek} sudah siap. Silakan tanda tangani.",
                route('contracts.show', $this),
                jenis: 'kontrak_siap_ttd',
                email: new KontrakSiapTtdMail($this),
                wa: "Halo {$penerima->name}, kontrak untuk proyek {$proyek} sudah siap ditandatangani. Silakan cek sistem.",
            );
        }
    }

    public function pastikanMasihBisaNego(): void
    {
        abort_if(
            in_array($this->status_partisipasi, [
                'deal', 'ditolak', 'diajukan_ke_client', 'lolos',
                'kontrak_ditandatangani', 'selesai_produksi', 'dibatalkan',
            ], true),
            422,
            'Negosiasi untuk pendaftar ini sudah tidak aktif.'
        );
    }

    public function bolehDilihatOleh(User $user): bool
    {
        return $user->bisaSebagaiAdmin()
            || ($user->role === 'extras' && $this->extras_id === $user->extrasProfile?->id);
    }

    private function kirimKonfirmasiFee(FeeNegotiation $negotiation, User $penerima): void
    {
        $keExtras = $penerima->id !== $this->castingProject->admin_id;
        $nominal = 'Rp '.number_format($negotiation->nominal, 0, ',', '.');

        $penerima->kabari(
            'Penawaran Fee',
            "Penawaran fee {$nominal} untuk proyek {$this->castingProject->namaKode()}.",
            route($keExtras ? 'extras.negotiations.show' : 'admin.negotiations.show', $this),
            jenis: 'nego_fee',
            email: new KonfirmasiFeeMail($negotiation),
        );
    }
}
