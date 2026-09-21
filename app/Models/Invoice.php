<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'casting_project_id', 'pdf_path', 'voucher_template_path',
    'catatan_invoice', 'ttd_admin_signature_path', 'ttd_cd_signature_path',
    'template_type', 'custom_doc_path',
])]
class Invoice extends Model
{
    public function castingProject(): BelongsTo
    {
        return $this->belongsTo(CastingProject::class);
    }
}
