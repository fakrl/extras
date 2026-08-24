<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['casting_project_id', 'pdf_path', 'ttd_admin_signature_path', 'ttd_cd_signature_path'])]
class Invoice extends Model
{
    public function castingProject(): BelongsTo
    {
        return $this->belongsTo(CastingProject::class);
    }
}
