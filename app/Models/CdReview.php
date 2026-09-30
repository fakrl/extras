<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['project_application_id', 'cd_id', 'keputusan', 'grade_cd'])]
class CdReview extends Model
{
    public function projectApplication(): BelongsTo
    {
        return $this->belongsTo(ProjectApplication::class);
    }

    public function cd(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cd_id');
    }
}
