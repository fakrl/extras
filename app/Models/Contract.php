<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['project_application_id', 'pdf_path', 'ttd_admin_signature_path', 'ttd_extras_signature_path', 'signed_at'])]
class Contract extends Model
{
    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
        ];
    }

    public function projectApplication(): BelongsTo
    {
        return $this->belongsTo(ProjectApplication::class);
    }

    public function isFullySigned(): bool
    {
        return $this->ttd_admin_signature_path && $this->ttd_extras_signature_path;
    }
}
