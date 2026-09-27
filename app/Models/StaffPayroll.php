<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['admin_project_assignment_id', 'nominal_pokok', 'pdf_slip_path', 'generated_at', 'status_bayar', 'dibayar_at'])]
class StaffPayroll extends Model
{
    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'dibayar_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(AdminProjectAssignment::class, 'admin_project_assignment_id');
    }

    public function addons(): MorphMany
    {
        return $this->morphMany(PaymentAddon::class, 'addable');
    }

    /**
     * RF-48: nominal total = pokok + semua add-on (RF-47).
     */
    public function nominalTotal(): float
    {
        return (float) $this->nominal_pokok + (float) $this->addons()->sum('nominal');
    }

    /**
     * SPEC AV.3: Cek apakah honor staf sudah dibayarkan.
     */
    public function isDibayar(): bool
    {
        return $this->status_bayar === 'sudah';
    }

    /**
     * SPEC AV.3: Tandai honor staf sudah dibayarkan.
     */
    public function tandaiDibayar(): void
    {
        $this->update([
            'status_bayar' => 'sudah',
            'dibayar_at' => now(),
        ]);
    }

    /**
     * RF-48: auto-generate slip honor PDF, dipanggil setelah nominal fix.
     */
    public function tandaiSlipDibuat(string $pdfPath): void
    {
        $this->update([
            'pdf_slip_path' => $pdfPath,
            'generated_at' => now(),
        ]);
    }
}
