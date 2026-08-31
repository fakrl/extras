<?php

namespace App\Models;

use App\Mail\HasilSeleksiMail;
use App\Mail\KonfirmasiFeeMail;
use App\Services\WhatsAppService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Mail;

#[Fillable(['casting_project_id', 'extras_id', 'casting_project_class_id', 'status_partisipasi', 'grade', 'fee_final', 'bentrok_jadwal_flag', 'alasan_tolak'])]
class ProjectApplication extends Model
{
    const STATUS_AKTIF = ['deal', 'diajukan_ke_cd', 'direview_cd', 'lolos', 'kontrak_ditandatangani'];

    const STATUS_LOLOS_KE_ATAS = ['lolos', 'kontrak_ditandatangani', 'selesai_produksi'];

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

    public function cdReviews(): HasMany
    {
        return $this->hasMany(CdReview::class);
    }

    public function cancellations(): HasMany
    {
        return $this->hasMany(Cancellation::class);
    }

    public function fieldNotes(): HasMany
    {
        return $this->hasMany(FieldNote::class)->latest();
    }

    /**
     * RF-16: Admin ajukan penawaran fee awal. Ronde 1, selalu dari admin.
     */
    public function ajukanFeeAwal(float $nominal): FeeNegotiation
    {
        $this->update(['status_partisipasi' => 'nego_fee']);

        $negotiation = $this->feeNegotiations()->create([
            'round' => 1,
            'diajukan_oleh' => 'admin',
            'nominal' => $nominal,
            'aksi' => 'tawar',
        ]);

        $this->kirimKonfirmasiFee($negotiation, $this->extras->user);

        return $negotiation;
    }

    /**
     * RF-17/RF-18: counter dari salah satu pihak, ronde bertambah, tidak
     * dibatasi jumlah putaran (mekanisme ala InDrive).
     */
    public function counterFee(string $diajukanOleh, float $nominal): FeeNegotiation
    {
        $roundTerakhir = $this->feeNegotiations()->max('round') ?? 0;

        $negotiation = $this->feeNegotiations()->create([
            'round' => $roundTerakhir + 1,
            'diajukan_oleh' => $diajukanOleh,
            'nominal' => $nominal,
            'aksi' => 'counter',
        ]);

        $penerima = $diajukanOleh === 'admin' ? $this->extras->user : $this->castingProject->admin;
        $this->kirimKonfirmasiFee($negotiation, $penerima);

        return $negotiation;
    }

    /**
     * RF-20: salah satu pihak terima -> status "Deal", fee terkunci.
     */
    public function terimaFee(string $diterimaOleh, float $nominal): FeeNegotiation
    {
        $roundTerakhir = $this->feeNegotiations()->max('round') ?? 0;

        $negotiation = $this->feeNegotiations()->create([
            'round' => $roundTerakhir + 1,
            'diajukan_oleh' => $diterimaOleh,
            'nominal' => $nominal,
            'aksi' => 'terima',
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
    public function tolakNegosiasi(string $ditolakOleh): FeeNegotiation
    {
        $roundTerakhir = $this->feeNegotiations()->max('round') ?? 0;

        $negotiation = $this->feeNegotiations()->create([
            'round' => $roundTerakhir + 1,
            'diajukan_oleh' => $ditolakOleh,
            'nominal' => 0,
            'aksi' => 'tolak',
        ]);

        $this->update(['status_partisipasi' => 'ditolak']);

        return $negotiation;
    }

    /**
     * RF-15 (perluasan): Admin bisa reject kandidat lebih dini — sebelum masuk
     * fase nego fee — kalau jelas tidak sesuai spesifikasi/kriteria tokoh yang
     * dicari. Beda dari tolakNegosiasi() (yang khusus fase nego) dan dari
     * keputusan CD (RF-23, fase review talent). Cuma boleh dipanggil selagi
     * status masih di fase awal, biar tidak bisa "menyalip" kandidat yang
     * sudah masuk nego/diajukan ke CD.
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
     * RF-21: hanya kandidat yang fee-nya sudah Deal yang boleh diajukan ke CD.
     * Ini penjaga urutan supaya alur "nego dulu, baru present ke CD" tidak
     * bisa dilewati dari controller mana pun.
     *
     * RF-22: re-cek bentrok jadwal di titik ini juga (bisa saja proyek lain
     * baru Deal setelah aplikasi ini Deal duluan). Non-blocking sama seperti
     * RF-13 di apply() — cuma re-set bentrok_jadwal_flag, tetap lanjut.
     * Return true kalau bentrok, supaya controller bisa kasih warning.
     */
    public function ajukanKeCd(): bool
    {
        if ($this->status_partisipasi !== 'deal') {
            throw new \LogicException('Kandidat hanya bisa diajukan ke CD setelah fee Deal.');
        }

        $tanggalProyekIni = $this->castingProject->shootingDates->pluck('tanggal');
        $adaBentrok = $this->extras->activeShootingDates($this->id)->intersect($tanggalProyekIni)->isNotEmpty();

        $this->update([
            'status_partisipasi' => 'diajukan_ke_cd',
            'bentrok_jadwal_flag' => $adaBentrok,
        ]);

        return $adaBentrok;
    }

    /**
     * RF-33/34: Admin atau Extras membatalkan aplikasi berstatus Deal, Lolos,
     * atau Kontrak Ditandatangani (diperluas SPEC.md Bagian C, 31 Agu 2026 —
     * sebelumnya cuma Deal, bikin RF-08 nyaris mustahil kejadian karena
     * kandidat biasanya sudah lewat Deal saat mendekati tanggal shooting).
     * Tidak termasuk Selesai Produksi (sudah kelar, tidak masuk akal batal)
     * atau status pra-Lolos (belum ada komitmen shooting yang bisa dibatalkan
     * mendadak). Satu klik langsung final (tidak ada approval dua pihak —
     * konfirmasi Fakrul 29 Agu 2026), konsisten dengan pola aksi sepihak lain
     * di sini.
     * RF-08: kalau pembatalan mendadak (< H-2 dari tanggal shooting
     * terdekat proyek ini), trigger hitungan cancel_count di ExtrasProfile.
     */
    public function batalkan(string $olehSiapa, string $alasan): Cancellation
    {
        if (! in_array($this->status_partisipasi, ['deal', 'lolos', 'kontrak_ditandatangani'], true)) {
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
     * RF-36: notif hasil seleksi (lolos/ditolak) ke Extras — dipicu dari
     * tolakDini() di sini, dan dari Cd\ReviewController setelah approve/reject.
     * Email adalah efek samping, bukan syarat sukses aksi utama.
     */
    public function kirimNotifikasiHasil(): void
    {
        $user = $this->extras->user;

        try {
            Mail::to($user)->queue(new HasilSeleksiMail($this));
            NotificationLog::catat($user->id, 'hasil_seleksi', true);
        } catch (\Throwable $e) {
            NotificationLog::catat($user->id, 'hasil_seleksi', false);
        }

        $pesan = $this->status_partisipasi === 'lolos'
            ? "Halo {$user->name}, selamat! Kamu LOLOS seleksi untuk proyek {$this->castingProject->nama_produksi}. Cek sistem untuk info lebih lanjut."
            : "Halo {$user->name}, mohon maaf, kamu belum lolos seleksi untuk proyek {$this->castingProject->nama_produksi} kali ini.";

        app(WhatsAppService::class)->kirimNotifikasi($user, 'hasil_seleksi', $pesan);
    }

    /**
     * RF-37: konfirmasi WA begitu Extras berhasil apply — titik ini belum
     * punya notif email sama sekali (WA satu-satunya kanal di sini, bukan
     * pelengkap email seperti 3 event lain).
     */
    public function kirimKonfirmasiApply(): void
    {
        $user = $this->extras->user;
        $pesan = "Halo {$user->name}, pendaftaranmu untuk proyek {$this->castingProject->nama_produksi} berhasil diterima. Admin akan segera mereview.";

        app(WhatsAppService::class)->kirimNotifikasi($user, 'konfirmasi_apply', $pesan);
    }

    public function pastikanMasihBisaNego(): void
    {
        abort_if(
            in_array($this->status_partisipasi, [
                'deal', 'ditolak', 'diajukan_ke_cd', 'direview_cd', 'lolos',
                'kontrak_ditandatangani', 'selesai_produksi', 'dibatalkan',
            ], true),
            422,
            'Negosiasi untuk pendaftar ini sudah tidak aktif.'
        );
    }

    public function bolehDilihatOleh(User $user): bool
    {
        return $user->role === 'admin_default'
            || ($user->role === 'extras' && $this->extras_id === $user->extrasProfile->id);
    }

    private function kirimKonfirmasiFee(FeeNegotiation $negotiation, User $penerima): void
    {
        try {
            Mail::to($penerima)->queue(new KonfirmasiFeeMail($negotiation));
            NotificationLog::catat($penerima->id, 'nego_fee', true);
        } catch (\Throwable $e) {
            NotificationLog::catat($penerima->id, 'nego_fee', false);
        }
    }
}
