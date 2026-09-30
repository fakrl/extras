<?php

namespace App\Console\Commands;

use App\Models\CastingProject;
use App\Models\ProjectApplication;
use Illuminate\Console\Command;

/**
 * RF-37: reminder H-1 shooting (in-app + WA), jalan harian lewat scheduler
 * (routes/console.php). Cuma ke Extras yang aplikasinya Deal ke atas.
 * BM.1: anti-dobel per Extras + proyek + tanggal lewat kunci notifikasi.
 */
class ReminderH1ShootingCommand extends Command
{
    protected $signature = 'reminder:h1-shooting';

    protected $description = 'Kirim WA reminder H-1 ke Extras yang jadwal shooting-nya besok';

    public function handle(): int
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
                $user->kabari(
                    'Shooting Besok',
                    "Pengingat: kamu dijadwalkan shooting besok untuk proyek {$project->nama_produksi}.",
                    route('extras.dashboard').'#pendaftaran-'.$application->id,
                    jenis: 'reminder_h1',
                    wa: "Halo {$user->name}, pengingat: kamu dijadwalkan shooting BESOK untuk proyek {$project->nama_produksi}. Jangan lupa persiapannya ya.",
                    kunci: "reminder_h1:{$project->id}:{$besok}",
                );
            }
        }

        return self::SUCCESS;
    }
}
