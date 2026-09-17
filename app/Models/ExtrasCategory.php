<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['nama'])]
class ExtrasCategory extends Model
{
    public function extrasProfiles(): BelongsToMany
    {
        return $this->belongsToMany(ExtrasProfile::class);
    }
}
