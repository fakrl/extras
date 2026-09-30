<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Console\Command;

class HapusMangkrakCommand extends Command
{
    protected $signature = 'akun:hapus-mangkrak';

    protected $description = 'BN.2: hapus akun mangkrak yang sudah diperingatkan >= 7 hari lalu';

    public function handle(): int
    {
        $users = User::mangkrak()->sudahDiperingatkan(now()->subDays(7))->with('extrasProfile')->get();
        $username = $users->pluck('username')->filter()->values()->all();
        $count = User::hapusMangkrak($users);

        if ($count) {
            ActivityLog::record(
                'AUTO_PRUNE_ABANDONED_USERS',
                "Sistem menghapus otomatis {$count} akun extras mangkrak (sudah diperingatkan >= 7 hari)",
                null,
                ['oleh' => 'sistem', 'jumlah_akun_dihapus' => $count, 'username' => $username]
            );
        }

        $this->info("{$count} akun dihapus.");

        return self::SUCCESS;
    }
}
