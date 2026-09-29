<?php

namespace App\Support;

use Illuminate\Http\Request;

class PerHalaman
{
    public const TABEL = [10, 25, 50, 100];

    public const KARTU = [12, 24, 48, 96];

    public static function dari(Request $request, int $default, array $pilihan): int
    {
        $per = filter_var($request->query('per'), FILTER_VALIDATE_INT);

        return in_array($per, $pilihan, true) ? $per : $default;
    }
}
