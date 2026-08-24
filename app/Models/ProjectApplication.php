<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['casting_project_id', 'extras_id', 'status_partisipasi', 'grade', 'fee_final', 'bentrok_jadwal_flag', 'alasan_tolak'])]
class ProjectApplication extends Model
{
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

    /**
     * RF-16: Admin ajukan penawaran fee awal. Ronde 1, selalu dari admin.
     */
    public function ajukanFeeAwal(float $nominal): FeeNegotiation
    {
        $this->update(['status_partisipasi' => 'nego_fee']);

        return $this->feeNegotiations()->create([
            'round' => 1,
            'diajukan_oleh' => 'admin',
            'nominal' => $nominal,
            'aksi' => 'tawar',
        ]);
    }

    /**
     * RF-17/RF-18: counter dari salah satu pihak, ronde bertambah, tidak
     * dibatasi jumlah putaran (mekanisme ala InDrive).
     */
    public function counterFee(string $diajukanOleh, float $nominal): FeeNegotiation
    {
        $roundTerakhir = $this->feeNegotiations()->max('round') ?? 0;

        return $this->feeNegotiations()->create([
            'round' => $roundTerakhir + 1,
            'diajukan_oleh' => $diajukanOleh,
            'nominal' => $nominal,
            'aksi' => 'counter',
        ]);
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
    }

    /**
     * RF-21: hanya kandidat yang fee-nya sudah Deal yang boleh diajukan ke CD.
     * Ini penjaga urutan supaya alur "nego dulu, baru present ke CD" tidak
     * bisa dilewati dari controller mana pun.
     */
    public function ajukanKeCd(): void
    {
        if ($this->status_partisipasi !== 'deal') {
            throw new \LogicException('Kandidat hanya bisa diajukan ke CD setelah fee Deal.');
        }

        $this->update(['status_partisipasi' => 'diajukan_ke_cd']);
    }
}
