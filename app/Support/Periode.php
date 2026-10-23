<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * CQ.1: satu rumus periode (preset mundur/maju + custom), dipakai semua
 * halaman yang punya filter tanggal. Gantikan sistem preset terpisah lama
 * (DashboardController::periode()/PRESET/PRESET_KALENDER/rentangKalender()).
 */
class Periode
{
    const MUNDUR = [
        '7' => '7 hari terakhir',
        '14' => '14 hari terakhir',
        '30' => '30 hari terakhir',
        '60' => '60 hari terakhir',
        '90' => '90 hari terakhir',
        '365' => '1 tahun terakhir',
    ];

    const MAJU = [
        '+7' => '7 hari ke depan',
        '+30' => '30 hari ke depan',
    ];

    /** @return array{0: Carbon, 1: Carbon, 2: ?string} */
    public static function dari(Request $request, string $default): array
    {
        if ($custom = self::custom($request)) {
            return [...$custom, null];
        }

        $semua = self::MUNDUR + self::MAJU;
        $preset = array_key_exists((string) $request->query('periode'), $semua) ? $request->query('periode') : $default;

        return self::rentang($preset);
    }

    /**
     * CQ.1: dipakai halaman yang filter tanggalnya OPSIONAL (mis. daftar Proyek
     * Admin) - null kalau tidak ada custom dari/sampai valid, BUKAN fallback ke
     * default seperti dari()/dariSesi(). Reuse rumus parsing & swap yang sama,
     * tanpa preset (halaman ini tidak punya dropdown preset).
     *
     * @return ?array{0: Carbon, 1: Carbon}
     */
    public static function opsional(Request $request): ?array
    {
        return self::custom($request);
    }

    /** @return ?array{0: Carbon, 1: Carbon} */
    private static function custom(Request $request): ?array
    {
        $tgl = fn (string $k) => rescue(fn () => Carbon::createFromFormat('!Y-m-d', (string) $request->query($k)), null, false) ?: null;
        [$dari, $sampai] = [$tgl('dari'), $tgl('sampai')];

        if (! $dari || ! $sampai) {
            return null;
        }

        return $dari->gt($sampai) ? [$sampai, $dari] : [$dari, $sampai];
    }

    /**
     * CQ.3: ingat pilihan terakhir per halaman di session (bukan localStorage).
     * Tanpa input baru di query, pakai yang tersimpan di session; ada input →
     * hitung & simpan ulang. Reset otomatis saat logout (session dibersihkan).
     */
    public static function dariSesi(Request $request, string $sessionKey, string $default): array
    {
        $adaInput = $request->filled('periode') || ($request->filled('dari') && $request->filled('sampai'));
        if (! $adaInput && $request->session()->has($sessionKey)) {
            $request->query->add($request->session()->get($sessionKey));
        }

        [$dari, $sampai, $preset] = self::dari($request, $default);

        $request->session()->put($sessionKey, $preset
            ? ['periode' => $preset]
            : ['dari' => $dari->format('Y-m-d'), 'sampai' => $sampai->format('Y-m-d')]);

        return [$dari, $sampai, $preset];
    }

    private static function rentang(string $preset): array
    {
        if (str_starts_with($preset, '+')) {
            $n = max(1, (int) substr($preset, 1));

            return [today(), today()->copy()->addDays($n - 1), $preset];
        }

        $n = max(1, (int) $preset);

        return [today()->copy()->subDays($n - 1), today(), $preset];
    }

    public static function label(Carbon $dari, Carbon $sampai, ?string $preset): string
    {
        $semua = self::MUNDUR + self::MAJU;
        if ($preset && isset($semua[$preset])) {
            return $semua[$preset];
        }

        $fmt = $dari->year === $sampai->year ? 'd M' : 'd M Y';

        return $dari->translatedFormat($fmt).' – '.$sampai->translatedFormat('d M Y');
    }
}
