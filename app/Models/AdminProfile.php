<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'honor_nominal', 'created_by'])]
class AdminProfile extends Model
{
    protected function casts(): array
    {
        return [
            'honor_updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * RF-41: Super Admin adjust nominal honor, standing rate yang bisa diubah kapan saja.
     */
    public function updateHonor(float $nominal): void
    {
        $this->update([
            'honor_nominal' => $nominal,
            'honor_updated_at' => now(),
        ]);
    }
}
