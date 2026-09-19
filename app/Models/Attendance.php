<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['project_application_id', 'event_shooting_date_id', 'status', 'dicatat_oleh', 'catatan', 'foto_path', 'status_validasi', 'divalidasi_oleh', 'divalidasi_at'])]
class Attendance extends Model
{
    protected function casts(): array
    {
        return ['divalidasi_at' => 'datetime'];
    }

    public function projectApplication(): BelongsTo
    {
        return $this->belongsTo(ProjectApplication::class);
    }

    public function eventShootingDate(): BelongsTo
    {
        return $this->belongsTo(EventShootingDate::class);
    }

    public function dicatatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    public function divalidasiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'divalidasi_oleh');
    }
}
