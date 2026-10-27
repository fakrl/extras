<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * CR.1.1: rentang tanggal untuk angka/statistik (pola Stripe/GA). Preset lama
 * (14/60/90/365/+7/+30 mundur-maju generik, era CQ.1) dihapus - diganti preset
 * kalender yang umum dipakai. Navigasi jadwal/agenda pindah ke Timeline.
 */
class Periode
{
    const PRESET = [
        'hari_ini' => 'Hari ini',
        '7' => '7 hari terakhir',
        '30' => '30 hari terakhir',
        'bulan_ini' => 'Bulan ini',
        'bulan_lalu' => 'Bulan lalu',
        'tahun_ini' => 'Tahun ini',
    ];

    /** @return array{0: Carbon, 1: Carbon, 2: ?string} */
    public static function dari(Request $request, string $default): array
    {
        if ($custom = self::custom($request)) {
            return [...$custom, null];
        }

        $preset = array_key_exists((string) $request->query('periode'), self::PRESET) ? $request->query('periode') : $default;

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

    /** @return array{0: Carbon, 1: Carbon, 2: string} */
    private static function rentang(string $preset): array
    {
        return match ($preset) {
            'hari_ini' => [today(), today(), $preset],
            'bulan_ini' => [today()->copy()->startOfMonth(), today()->copy()->endOfMonth(), $preset],
            'bulan_lalu' => [today()->copy()->subMonthNoOverflow()->startOfMonth(), today()->copy()->subMonthNoOverflow()->endOfMonth(), $preset],
            'tahun_ini' => [today()->copy()->startOfYear(), today()->copy()->endOfYear(), $preset],
            default => [today()->copy()->subDays(max(1, (int) $preset) - 1), today(), $preset],
        };
    }

    public static function label(Carbon $dari, Carbon $sampai, ?string $preset): string
    {
        if ($preset && isset(self::PRESET[$preset])) {
            return self::PRESET[$preset];
        }

        $fmt = $dari->year === $sampai->year ? 'd M' : 'd M Y';

        return $dari->translatedFormat($fmt).' – '.$sampai->translatedFormat('d M Y');
    }
}
