<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['project_application_id', 'client_id', 'keputusan', 'grade_client'])]
class ClientReview extends Model
{
    /** CL-09: istilah Client untuk keputusan review. */
    public function label(): string
    {
        return $this->keputusan === 'approve' ? 'Dipilih' : 'Ditolak';
    }

    public function projectApplication(): BelongsTo
    {
        return $this->belongsTo(ProjectApplication::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }
}
