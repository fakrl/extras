<?php

namespace App\Support;

use App\Models\User;

/** DA.1: diekstrak dari AdminManagementController supaya UserManagementController ikut reuse, bukan duplikat. */
class Kredensial
{
    public static function passwordSementara(int $panjang = 10): string
    {
        $huruf = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        return collect(range(1, $panjang))->map(fn () => $huruf[random_int(0, strlen($huruf) - 1)])->implode('');
    }

    public static function buat(User $user, string $password): array
    {
        return [
            'nama' => $user->name,
            'username' => $user->username ?: $user->email,
            'password' => $password,
            'url' => route('login'),
            'nomor_wa' => $user->nomor_wa,
        ];
    }
}
