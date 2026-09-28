<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['nama', 'grup'])]
class ExtrasCategory extends Model
{
    public const GRUP = [
        'Usia tampilan' => ['Anak-anak', 'Remaja', 'Dewasa muda', 'Dewasa', 'Orang Tua', 'Lansia'],
        'Tampilan/Look' => ['Chinese/Tionghoa', 'Timur Tengah', 'Indonesia Timur', 'Kaukasia/Bule', 'Melayu', 'Jawa', 'Sunda'],
        'Tipe' => ['Mahasiswa', 'Pekerja kantoran', 'Atlet', 'Berhijab', 'Bertato', 'Rambut panjang'],
        'Kemampuan' => ['Naik motor', 'Nyetir mobil', 'Berenang', 'Menari', 'Bahasa daerah'],
    ];

    public function extrasProfiles(): BelongsToMany
    {
        return $this->belongsToMany(ExtrasProfile::class);
    }

    public function castingProjectClasses(): BelongsToMany
    {
        return $this->belongsToMany(CastingProjectClass::class);
    }

    /** BA.5: tag yang dicari peran-peran di satu proyek, buat chip filter. */
    public static function dicariDiProyek(int $projectId): Collection
    {
        return static::whereHas('castingProjectClasses', fn ($q) => $q->where('casting_project_id', $projectId))->orderBy('nama')->get();
    }

    public static function perGrup(): Collection
    {
        $urutan = collect(self::GRUP)->flatten()->flip();

        return static::all()
            ->sortBy(fn ($c) => [$urutan[$c->nama] ?? PHP_INT_MAX, $c->nama])
            ->groupBy(fn ($c) => $c->grup ?: 'Lainnya');
    }
}
