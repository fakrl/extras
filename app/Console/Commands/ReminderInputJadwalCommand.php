<?php

namespace App\Console\Commands;

use App\Models\EventShootingDate;
use Illuminate\Console\Command;

class ReminderInputJadwalCommand extends Command
{
    protected $signature = 'reminder:input-jadwal';

    protected $description = 'Kirim WA ke Client bila H-3 shooting belum ada jadwal detail yang diisi';

    public function handle(): int
    {
        $tanggalH3 = now()->addDays(3)->toDateString();

        $dates = EventShootingDate::whereDate('tanggal', $tanggalH3)
            ->whereNull('lokasi')
            ->with('castingProject.client')
            ->get();

        foreach ($dates as $date) {
            $project = $date->castingProject;
            $user = $project->client;
            $tgl = $date->tanggal->format('d M Y');

            $user?->kabari(
                'Jadwal Shooting Belum Lengkap',
                "Proyek {$project->namaKode()} shooting {$tgl} (H-3) tapi jadwal detail belum diisi.",
                route('client.jadwal.show', $project),
                jenis: 'reminder_input_jadwal',
                wa: "Halo {$user->name}, proyek {$project->namaKode()} ada shooting pada {$tgl} (H-3) tapi jadwal detail (lokasi, jam, daftar panggilan) belum diisi. Mohon segera lengkapi di sistem.",
                kunci: "reminder_input_jadwal:{$project->id}:{$tanggalH3}",
            );
        }

        return self::SUCCESS;
    }
}
