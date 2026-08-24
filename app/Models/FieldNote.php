<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['project_application_id', 'korlap_id', 'jenis', 'isi'])]
class FieldNote extends Model
{
    public function projectApplication(): BelongsTo
    {
        return $this->belongsTo(ProjectApplication::class);
    }

    public function korlap(): BelongsTo
    {
        return $this->belongsTo(User::class, 'korlap_id');
    }
}
