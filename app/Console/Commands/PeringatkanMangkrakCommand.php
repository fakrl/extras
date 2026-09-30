<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Mail\Mailable;

class PeringatkanMangkrakCommand extends Command
{
    protected $signature = 'akun:peringatkan-mangkrak';

    protected $description = 'BN.2: peringatkan Extras yang akunnya akan jadi mangkrak dalam 7 hari';

    public function handle(): int
    {
        $users = User::mangkrak(User::HARI_MANGKRAK - 7)
            ->whereDoesntHave('notifications', fn ($n) => $n->where('data->jenis', 'peringatan_mangkrak'))
            ->get();

        foreach ($users as $user) {
            $tanggal = $user->created_at->copy()->addDays(User::HARI_MANGKRAK)->max(now()->addDays(7))->translatedFormat('d F Y');
            $pesan = "Profilmu belum lengkap. Lengkapi sebelum {$tanggal} supaya akunmu nggak dihapus otomatis.";

            $user->kabari(
                'Akun Akan Dihapus',
                $pesan,
                route('extras.profile.edit'),
                jenis: 'peringatan_mangkrak',
                email: $user->email ? (new Mailable)->subject('Akun Akan Dihapus')->html(e($pesan)) : null,
                wa: "Halo {$user->name}, {$pesan}",
                kunci: 'peringatan_mangkrak',
            );
        }

        $this->info("{$users->count()} akun diperingatkan.");

        return self::SUCCESS;
    }
}
