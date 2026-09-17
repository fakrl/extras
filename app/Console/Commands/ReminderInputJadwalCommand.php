<?php

namespace App\Console\Commands;

use App\Models\EventShootingDate;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class ReminderInputJadwalCommand extends Command
{
    protected $signature = 'reminder:input-jadwal';

    protected $description = 'Kirim WA ke CD bila H-3 shooting belum ada jadwal detail yang diisi';

    public function handle(WhatsAppService $whatsapp): int
    {
        $tanggalH3 = now()->addDays(3)->toDateString();

        // Baris di event_shooting_dates yang tanggalnya H-3 tapi lokasi masih null
        $dates = EventShootingDate::whereDate('tanggal', $tanggalH3)
            ->whereNull('lokasi')
            ->with('castingProject.cdAssignments.cdUser')
            ->get();

        // Grup per proyek, satu WA per CD per proyek (bukan per baris tanggal)
        $sent = [];
        foreach ($dates as $date) {
            $project = $date->castingProject;
            foreach ($project->cdAssignments as $assignment) {
                $key = $project->id . '_' . $assignment->cd_user_id;
                if (isset($sent[$key])) {
                    continue;
                }
                $sent[$key] = true;
                $user = $assignment->cdUser;
                $pesan = "Halo {$user->name}, proyek {$project->nama_produksi} ada shooting pada " . $date->tanggal->format('d M Y') . " (H-3) tapi jadwal detail (lokasi, jam, daftar panggilan) belum diisi. Mohon segera lengkapi di sistem.";
                $whatsapp->kirimNotifikasi($user, 'reminder_input_jadwal', $pesan);
            }
        }

        return self::SUCCESS;
    }
}
