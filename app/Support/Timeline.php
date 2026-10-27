<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * CR.1.2: navigasi timeline untuk kartu Jadwal/agenda (Hari ini/Minggu ini/
 * Bulan ini + geser ‹ › + "Kembali ke hari ini"). Terpisah dari Periode, yang
 * dipakai untuk filter rentang angka/statistik.
 */
class Timeline
{
    const SKALA = ['hari' => 'Hari ini', 'minggu' => 'Minggu ini', 'bulan' => 'Bulan ini'];

    /** @return array{0: Carbon, 1: Carbon, 2: string} */
    public static function dariSesi(Request $request, string $sessionKey, string $defaultSkala = 'minggu'): array
    {
        $adaInput = $request->filled('skala') || $request->filled('mulai');
        if (! $adaInput && $request->session()->has($sessionKey)) {
            $request->query->add($request->session()->get($sessionKey));
        }

        $skala = array_key_exists((string) $request->query('skala'), self::SKALA) ? $request->query('skala') : $defaultSkala;
        $mulai = rescue(fn () => Carbon::createFromFormat('!Y-m-d', (string) $request->query('mulai')), null, false) ?: today();

        [$dari, $sampai] = self::rentang($mulai, $skala);

        $request->session()->put($sessionKey, ['skala' => $skala, 'mulai' => $mulai->format('Y-m-d')]);

        return [$dari, $sampai, $skala];
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private static function rentang(Carbon $mulai, string $skala): array
    {
        return match ($skala) {
            'hari' => [$mulai->copy(), $mulai->copy()],
            'bulan' => [$mulai->copy()->startOfMonth(), $mulai->copy()->endOfMonth()],
            default => [$mulai->copy()->startOfWeek(), $mulai->copy()->endOfWeek()],
        };
    }

    public static function geser(Carbon $dari, string $skala, int $arah): string
    {
        $t = match ($skala) {
            'hari' => $dari->copy()->addDays($arah),
            'bulan' => $dari->copy()->addMonthNoOverflow($arah),
            default => $dari->copy()->addWeeks($arah),
        };

        return $t->format('Y-m-d');
    }

    public static function label(Carbon $dari, Carbon $sampai): string
    {
        if ($dari->isSameDay($sampai)) {
            return $dari->translatedFormat('d M Y');
        }

        $fmt = $dari->year === $sampai->year ? 'd M' : 'd M Y';

        return $dari->translatedFormat($fmt).' – '.$sampai->translatedFormat('d M Y');
    }

    public static function iniSekarang(Carbon $dari, Carbon $sampai): bool
    {
        return $dari->lte(today()) && $sampai->gte(today());
    }
}
