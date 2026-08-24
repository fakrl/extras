<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// SENGAJA tidak ada #[Fillable(...)] — insert cuma boleh lewat
// ProjectApplication::batalkan() (nanti ditambahkan pas modul Pembatalan
// dikerjakan), supaya cancel_count di ExtrasProfile selalu ikut ter-update
// konsisten, tidak ada cancellation "yatim" yang lupa nge-trigger hitungan.
class Cancellation extends Model
{
    protected function casts(): array
    {
        return [
            'is_mendadak' => 'boolean',
        ];
    }

    public function projectApplication(): BelongsTo
    {
        return $this->belongsTo(ProjectApplication::class);
    }
}
