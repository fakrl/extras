<?php

namespace App\Console\Commands;

use App\Models\EventShootingDate;
use Illuminate\Console\Command;

class ReminderH3PilihExtrasCommand extends Command
{
    protected $signature = 'reminder:h3-pilih-extras';

    protected $description = 'Kirim WA ke Client bila H-3 atau H-1 shooting masih ada kandidat belum dipilih';

    public function handle(): int
    {
        $tanggal = [now()->addDays(3)->toDateString(), now()->addDay()->toDateString()];

        $dates = EventShootingDate::where(fn ($q) => $q->whereDate('tanggal', $tanggal[0])->orWhereDate('tanggal', $tanggal[1]))
            ->whereHas('castingProject', fn ($q) => $q->whereNotNull('client_id')
                ->whereHas('applications', fn ($a) => $a->where('status_partisipasi', 'diajukan_ke_client')))
            ->with('castingProject.client')
            ->get()
            ->unique('casting_project_id');

        foreach ($dates as $date) {
            $project = $date->castingProject;
            $user = $project->client;
            $jumlah = $project->applications()->where('status_partisipasi', 'diajukan_ke_client')->count();

            $user?->kabari(
                'Kandidat Belum Direview',
                "Proyek {$project->namaKode()} mendekati tanggal shooting dan masih ada {$jumlah} kandidat yang belum kamu review.",
                route('client.reviews.show', $project),
                jenis: 'reminder_h3_pilih_extras',
                wa: "Halo {$user->name}, proyek {$project->namaKode()} mendekati tanggal shooting dan masih ada {$jumlah} kandidat yang belum kamu review. Mohon segera lakukan review di sistem.",
                kunci: "reminder_h3_pilih_extras:{$project->id}:{$date->tanggal->toDateString()}",
            );
        }

        return self::SUCCESS;
    }
}
