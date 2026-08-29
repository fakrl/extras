<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Whitelist teknis 3 kolom yang dipakai ProjectApplication::batalkan() —
// proteksinya bukan dari sini, tapi karena tidak ada controller yang panggil
// Cancellation::create() langsung (semua insert lewat batalkan(), scalar
// param typed, bukan raw request), sama seperti pola FeeNegotiation.
#[Fillable(['dibatalkan_oleh', 'alasan', 'is_mendadak'])]
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
