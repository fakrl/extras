<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['casting_project_id', 'user_id', 'assigned_by', 'status_log'])]
class AdminProjectAssignment extends Model
{
    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function castingProject(): BelongsTo
    {
        return $this->belongsTo(CastingProject::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function payroll(): HasOne
    {
        return $this->hasOne(StaffPayroll::class);
    }

    /**
     * RF-45/RF-46: proyek selesai -> log jadi "selesai" -> dasar kelayakan honor.
     * Dipanggil dari logic penyelesaian proyek, bukan dari request bebas.
     */
    public function tandaiSelesai(): StaffPayroll
    {
        $this->update([
            'status_log' => 'selesai',
            'completed_at' => now(),
        ]);

        $honorNominal = $this->user->adminProfile?->honor_nominal ?? 0;

        return $this->payroll()->create([
            'nominal_pokok' => $honorNominal,
        ]);
    }
}
