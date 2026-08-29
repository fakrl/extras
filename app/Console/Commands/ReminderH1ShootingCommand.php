<?php

namespace App\Console\Commands;

use App\Models\CastingProject;
use App\Models\ProjectApplication;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

/**
 * RF-37: reminder WA H-1 shooting, jalan harian lewat scheduler
 * (routes/console.php). Cuma kirim ke Extras yang aplikasinya sudah Deal
 * ke atas (bukan yang masih nego/ditolak/dibatalkan) — sama seperti daftar
 * status "aktif" yang dipakai deteksi bentrok jadwal di tempat lain.
 */
class ReminderH1ShootingCommand extends Command
{
    protected $signature = 'reminder:h1-shooting';

    protected $description = 'Kirim WA reminder H-1 ke Extras yang jadwal shooting-nya besok';

    public function handle(WhatsAppService $whatsapp): int
    {
        $besok = now()->addDay()->toDateString();

        $projects = CastingProject::whereHas('shootingDates', fn ($q) => $q->whereDate('tanggal', $besok))
            ->with(['applications' => function ($q) {
                $q->whereIn('status_partisipasi', ProjectApplication::STATUS_AKTIF)->with('extras.user');
            }])
            ->get();

        foreach ($projects as $project) {
            foreach ($project->applications as $application) {
                $user = $application->extras->user;
                $pesan = "Halo {$user->name}, pengingat: kamu dijadwalkan shooting BESOK untuk proyek {$project->nama_produksi}. Jangan lupa persiapannya ya.";
                $whatsapp->kirimNotifikasi($user, 'reminder_h1', $pesan);
            }
        }

        return self::SUCCESS;
    }
}
