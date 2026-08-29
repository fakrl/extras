<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['casting_project_id', 'nama_kelas', 'kriteria', 'budget_client', 'kuota_kelas'])]
class CastingProjectClass extends Model
{
    protected function casts(): array
    {
        return [
            'kriteria' => 'array',
        ];
    }

    public function castingProject(): BelongsTo
    {
        return $this->belongsTo(CastingProject::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ProjectApplication::class);
    }
}
