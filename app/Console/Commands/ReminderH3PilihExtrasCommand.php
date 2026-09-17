<?php

namespace App\Console\Commands;

use App\Models\CastingProject;
use App\Models\ProjectApplication;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class ReminderH3PilihExtrasCommand extends Command
{
    protected $signature = 'reminder:h3-pilih-extras';

    protected $description = 'Kirim WA ke CD bila H-3 atau H-1 shooting masih ada kandidat belum dipilih';

    public function handle(WhatsAppService $whatsapp): int
    {
        $tanggalH3 = now()->addDays(3)->toDateString();
        $tanggalH1 = now()->addDay()->toDateString();

        $projektIds = \App\Models\EventShootingDate::whereIn('tanggal', [$tanggalH3, $tanggalH1])
            ->pluck('casting_project_id')
            ->unique();

        $projects = CastingProject::whereIn('id', $projektIds)
            ->whereHas('applications', fn ($q) => $q->where('status_partisipasi', 'diajukan_ke_cd'))
            ->with('cdAssignments.cdUser')
            ->get();

        foreach ($projects as $project) {
            $jumlah = $project->applications()
                ->where('status_partisipasi', 'diajukan_ke_cd')
                ->count();

            foreach ($project->cdAssignments as $assignment) {
                $user = $assignment->cdUser;
                $pesan = "Halo {$user->name}, proyek {$project->nama_produksi} mendekati tanggal shooting dan masih ada {$jumlah} kandidat yang belum kamu review. Mohon segera lakukan review di sistem.";
                $whatsapp->kirimNotifikasi($user, 'reminder_h3_pilih_extras', $pesan);
            }
        }

        return self::SUCCESS;
    }
}
