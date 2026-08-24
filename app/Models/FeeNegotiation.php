<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// SENGAJA tidak ada #[Fillable(...)] — tabel ini append-only, satu-satunya
// cara insert yang legit adalah lewat method di ProjectApplication
// (ajukanFeeAwal/counterFee/terimaFee/tolakNegosiasi), bukan create() langsung
// dari controller. Ini jejak audit negosiasi, tidak boleh ditembus sembarang endpoint.
class FeeNegotiation extends Model
{
    public function projectApplication(): BelongsTo
    {
        return $this->belongsTo(ProjectApplication::class);
    }
}
