<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['casting_project_id', 'tanggal', 'lokasi', 'jam_mulai', 'jam_selesai', 'catatan', 'panggilan'])]
class EventShootingDate extends Model
{
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'panggilan' => 'array',
        ];
    }

    public function castingProject(): BelongsTo
    {
        return $this->belongsTo(CastingProject::class);
    }
}
