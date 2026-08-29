<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['project_application_id', 'status', 'bukti_transfer_path', 'ditransfer_at', 'dikonfirmasi_at'])]
class Payment extends Model
{
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
}
