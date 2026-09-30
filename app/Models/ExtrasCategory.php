<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Validation\ValidationException;

#[Fillable(['nama', 'grup', 'dibuat_oleh'])]
class ExtrasCategory extends Model
{
    public const GRUP = [
        'Usia tampilan' => ['Anak-anak', 'Remaja', 'Dewasa muda', 'Dewasa', 'Orang Tua', 'Lansia'],
        'Tampilan/Look' => ['Chinese/Tionghoa', 'Timur Tengah', 'Indonesia Timur', 'Kaukasia/Bule', 'Melayu', 'Jawa', 'Sunda'],
        'Warna kulit' => ['Kuning langsat', 'Sawo matang', 'Putih', 'Gelap'],
        'Tipe' => ['Mahasiswa', 'Pekerja kantoran', 'Atlet', 'Berhijab', 'Bertato', 'Rambut panjang'],
        'Kemampuan' => ['Naik motor', 'Nyetir mobil', 'Berenang', 'Menari', 'Bahasa daerah'],
    ];

    public const MAKS = 15;

    /** D22: grup yang nggak tampil di profil publik. */
    public const GRUP_PRIVAT = ['Tampilan/Look', 'Warna kulit', 'Lainnya'];

    public function extrasProfiles(): BelongsToMany
    {
        return $this->belongsToMany(ExtrasProfile::class);
    }

    public function castingProjectClasses(): BelongsToMany
    {
        return $this->belongsToMany(CastingProjectClass::class);
    }

    /** BR.5: tag tanpa grup (Lainnya), atau tag non-bawaan yang dipakai ≤1 Extras (kemungkinan typo). */
    public function scopePerluDirapikan($query)
    {
        return $query->where(fn ($q) => $q->whereNull('grup')
            ->orWhere(fn ($w) => $w->has('extrasProfiles', '<=', 1)->whereNotIn('nama', array_merge(...array_values(self::GRUP)))));
    }

    /** BJ.1: trim, buang # depan, rapikan spasi, maks 30 karakter; kosong → null. */
    public static function normalisasi(string $nama): ?string
    {
        $nama = trim(mb_substr(trim(preg_replace('/\s+/u', ' ', ltrim(trim($nama), '#'))), 0, 30));

        return $nama === '' ? null : $nama;
    }

    /** BJ.1: cocok tanpa beda huruf besar/kecil; belum ada → tag baru grup null (Lainnya). */
    public static function cariAtauBuat(string $nama, ?User $pembuat = null): self
    {
        $ada = static::whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])->first();
        if ($ada) {
            return $ada;
        }

        $tag = static::create(['nama' => $nama, 'grup' => null, 'dibuat_oleh' => $pembuat?->id]);
        ActivityLog::record('TAG_DIBUAT', ($pembuat ? "{$pembuat->label()} {$pembuat->name}" : 'Sistem')." membuat tag baru #{$nama}", $tag, [], $pembuat);

        return $tag;
    }

    /** Teks warna_kulit lama → tag grup "Warna kulit" ("hitam" = Gelap). */
    public static function tagWarnaKulit(string $warna): ?self
    {
        $nama = static::normalisasi($warna);
        if (! $nama) {
            return null;
        }
        $kecil = mb_strtolower($nama);
        $nama = collect(self::GRUP['Warna kulit'])->first(fn ($n) => mb_strtolower($n) === $kecil)
            ?? ($kecil === 'hitam' ? 'Gelap' : mb_strtoupper(mb_substr($kecil, 0, 1)).mb_substr($kecil, 1));
        $tag = static::cariAtauBuat($nama);
        if (! $tag->grup) {
            $tag->update(['grup' => 'Warna kulit']);
        }

        return $tag;
    }

    /**
     * BJ.1: gabung input nama tag bebas + id lama jadi daftar id (tag baru dibuat), maks 15.
     *
     * @return list<int>
     */
    public static function idsDariInput(array $nama, array $ids = [], ?User $pembuat = null, string $field = 'tag_nama'): array
    {
        $nama = collect($nama)->map(fn ($n) => is_string($n) ? static::normalisasi($n) : null)
            ->filter()->unique(fn ($n) => mb_strtolower($n));
        $ids = collect($ids)->map(fn ($i) => (int) $i)->unique();
        if ($nama->count() + $ids->count() > self::MAKS) {
            throw ValidationException::withMessages([$field => 'Maksimal '.self::MAKS.' tag.']);
        }

        return $ids->merge($nama->map(fn ($n) => static::cariAtauBuat($n, $pembuat)->id))->unique()->values()->all();
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
