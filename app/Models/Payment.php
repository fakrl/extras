<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['project_application_id', 'status', 'bukti_transfer_path', 'ditransfer_at', 'dikonfirmasi_at', 'alasan_sengketa'])]
class Payment extends Model
{
    const LABELS = [
        'belum_dibayar' => 'Belum Dibayar',
        'ditransfer' => 'Sudah Ditransfer',
        'dikonfirmasi_diterima' => 'Dikonfirmasi Diterima',
        'disengketakan' => 'Disengketakan',
    ];

    const BADGES = [
        'belum_dibayar' => 'badge-netral',
        'ditransfer' => 'badge-info',
        'dikonfirmasi_diterima' => 'badge-aktif',
        'disengketakan' => 'badge-pending',
    ];

    /** BQ.1: satu definisi "perlu ditransfer" = kontrak sudah TTD lengkap + belum dibayar. */
    public function scopePerluDitransfer(Builder $query): void
    {
        $query->where('status', 'belum_dibayar')
            ->whereHas('projectApplication', fn ($a) => $a->where('status_partisipasi', 'kontrak_ditandatangani'));
    }

    public function menungguKontrak(): bool
    {
        return $this->status === 'belum_dibayar' && $this->project_application_id && $this->projectApplication?->status_partisipasi !== 'kontrak_ditandatangani';
    }

    public function label(): string
    {
        return $this->menungguKontrak() ? 'Menunggu kontrak' : (self::LABELS[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status)));
    }

    public function badgeClass(): string
    {
        return self::BADGES[$this->status] ?? 'badge-netral';
    }

    protected function casts(): array
    {
        return [
            'ditransfer_at' => 'datetime',
            'dikonfirmasi_at' => 'datetime',
        ];
    }

    public function projectApplication(): BelongsTo
    {
        return $this->belongsTo(ProjectApplication::class);
    }

    public function addons(): MorphMany
    {
        return $this->morphMany(PaymentAddon::class, 'addable');
    }

    /**
     * Honor pokok Extras = fee_final hasil deal nego (tabel payments sendiri
     * nggak punya kolom nominal, beda dengan StaffPayroll::nominal_pokok).
     */
    public function nominalPokok(): float
    {
        return (float) ($this->projectApplication?->fee_final ?? 0);
    }

    /**
     * Pokok + semua add-on. Pakai relasi yang sudah di-load kalau ada (hindari N+1).
     */
    public function nominalTotal(): float
    {
        return $this->nominalPokok() + (float) $this->addons->sum('nominal');
    }

    /**
     * RF-28: Admin menandai transfer + upload bukti.
     */
    public function tandaiDitransfer(string $buktiPath): void
    {
        $this->update([
            'status' => 'ditransfer',
            'bukti_transfer_path' => $buktiPath,
            'ditransfer_at' => now(),
        ]);
    }

    /**
     * RF-29: Extras konfirmasi penerimaan.
     */
    public function konfirmasiDiterima(): void
    {
        $this->update([
            'status' => 'dikonfirmasi_diterima',
            'dikonfirmasi_at' => now(),
        ]);
    }

    public function tandaiDisengketakan(string $alasan): void
    {
        $this->update([
            'status' => 'disengketakan',
            'alasan_sengketa' => $alasan,
        ]);
    }
}
