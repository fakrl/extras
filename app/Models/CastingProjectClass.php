<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'casting_project_id', 'nama_kelas', 'kriteria', 'budget_client', 'kuota_kelas',
    'jam_callsheet', 'jam_callingan', 'keterangan_scene', 'tipe_continuity',
])]
class CastingProjectClass extends Model
{
    protected function casts(): array
    {
        return [
            'kriteria' => 'string',
        ];
    }

    // MySQL balikin kolom TIME sebagai HH:MM:SS, SQLite apa adanya; UI cukup HH:MM.
    protected function jamCallsheet(): Attribute
    {
        return Attribute::get(fn (?string $v) => $v ? substr($v, 0, 5) : null);
    }

    protected function jamCallingan(): Attribute
    {
        return Attribute::get(fn (?string $v) => $v ? substr($v, 0, 5) : null);
    }

    public function castingProject(): BelongsTo
    {
        return $this->belongsTo(CastingProject::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ProjectApplication::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ExtrasCategory::class);
    }

    /** BD.7: terisi = aplikasi kelas yang tidak ditolak/dibatalkan (sama dgn CastingProject::kuotaPenuh). */
    public function scopeWithTerisi(Builder $query): void
    {
        $query->withCount(['applications as terisi' => fn ($q) => $q->whereNotIn('status_partisipasi', ['ditolak', 'dibatalkan'])]);
    }

    /** BA.3: % tag peran yang dimiliki. Null kalau peran tanpa tag. Eager-load `categories`. */
    public function persenCocok(array $tagDimiliki): ?int
    {
        $dicari = $this->categories->modelKeys();

        return $dicari ? (int) round(count(array_intersect($dicari, $tagDimiliki)) / count($dicari) * 100) : null;
    }

    public function sisaKuota(): int
    {
        return max(0, (int) $this->kuota_kelas - (int) ($this->terisi ?? 0));
    }
}
