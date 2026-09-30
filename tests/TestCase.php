<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /** BM.1: status kirim email/WA ada di notifications.data. */
    protected function notifikasi(int $userId, string $jenis): array
    {
        return DB::table('notifications')->where('notifiable_id', $userId)->get()
            ->map(fn ($n) => json_decode($n->data, true))
            ->where('jenis', $jenis)->values()->all();
    }

    protected function assertNotifikasi(int $userId, string $jenis, array $status = []): void
    {
        $cocok = collect($this->notifikasi($userId, $jenis))
            ->filter(fn ($d) => collect($status)->every(fn ($v, $k) => array_key_exists($k, $d) && $d[$k] === $v));
        $this->assertTrue($cocok->isNotEmpty(), "Notifikasi {$jenis} ".json_encode($status)." untuk user {$userId} tidak ada.");
    }
}
