<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/** BI.2: chip filter aktif + URL hapusnya (param lain tetap). */
class FilterAktif
{
    /**
     * @param  array<int, array{0: string|string[], 1: string, 2?: string|int}|null>  $filter  [param(s), label, nilai (untuk param array)]
     * @return array<int, array{label: string, url: string}>
     */
    public static function chips(Request $request, array $filter): array
    {
        $query = Arr::except($request->query(), 'page');

        return array_values(array_map(function ($f) use ($request, $query) {
            [$param, $label] = $f;
            if (isset($f[2])) {
                $query[$param] = array_values(array_diff((array) ($query[$param] ?? []), [$f[2]]));
            } else {
                $query = Arr::except($query, (array) $param);
            }

            return ['label' => $label, 'url' => self::url($request, $query)];
        }, array_filter($filter)));
    }

    public static function url(Request $request, array $query): string
    {
        $query = array_filter($query, fn ($v) => $v !== null && $v !== '' && $v !== []);

        return $request->url().($query ? '?'.Arr::query($query) : '');
    }

    /** BQ.2: Reset = kosongkan semua filter + pencarian, balik halaman 1; cuma `per` & `tampil` (preferensi tampilan) yang dipertahankan. */
    public static function reset(Request $request): string
    {
        return self::url($request, Arr::only($request->query(), ['per', 'tampil']));
    }

    /** "Hapus semua" chip = hapus filter yang tampil sebagai chip; pencarian tetap. */
    public static function hapusSemua(Request $request, array $pertahankan = []): string
    {
        return self::url($request, Arr::only($request->query(), ['q', 'per', 'tampil', ...$pertahankan]));
    }
}
