<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'casting_project_id', 'pdf_path', 'voucher_template_path',
    'catatan_invoice', 'ttd_admin_signature_path', 'ttd_cd_signature_path',
    'template_type', 'custom_doc_path', 'nominal', 'status_bayar', 'dibayar_at',
])]
class Invoice extends Model
{
    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'dibayar_at' => 'datetime',
        ];
    }

    public function isLunas(): bool
    {
        return $this->status_bayar === 'lunas';
    }

    public function castingProject(): BelongsTo
    {
        return $this->belongsTo(CastingProject::class);
    }
}
