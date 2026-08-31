<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['casting_project_id', 'cd_user_id'])]
class CdProjectAssignment extends Model
{
    public function castingProject(): BelongsTo
    {
        return $this->belongsTo(CastingProject::class);
    }

    public function cdUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cd_user_id');
    }
}
