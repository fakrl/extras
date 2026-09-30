<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\CastingProject;
use App\Models\EventShootingDate;
use App\Models\FieldNote;
use App\Models\ProjectApplication;
use Illuminate\Support\Collection;

// BL.2: satu sumber data absensi, dipakai halaman Absensi & pratinjau Monitoring Korlap SA.
class KorlapRingkasan
{
    public static function peserta(CastingProject $project, EventShootingDate $shootingDate): Collection
    {
        return $project->applications()
            ->whereIn('status_partisipasi', ProjectApplication::STATUS_AKTIF)
            ->with(['extras.user', 'castingProjectClass', 'attendances.divalidasiOleh'])
            ->get()
            ->each(fn ($a) => $a->setRelation('absen', $a->attendances->firstWhere('event_shooting_date_id', $shootingDate->id)))
            ->sortBy(fn ($a) => [
                $a->absen && $a->absen->status_validasi !== 'menunggu' ? 1 : 0,
                $a->jam_callingan ?: $a->castingProjectClass?->jam_callingan ?: '99:99',
            ]);
    }

    public static function rekap(Collection $peserta): array
    {
        $hitung = fn (callable $f) => $peserta->filter(fn ($a) => $f($a->absen))->count();
        $validasi = $hitung(fn ($x) => $x?->status_validasi === 'menunggu');
        $belum = $hitung(fn ($x) => ! $x);

        return [
            'total' => $peserta->count(),
            'hadir' => $hitung(fn ($x) => $x?->status === 'hadir' && $x->status_validasi === 'tervalidasi'),
            'menunggu_validasi' => $validasi,
            'belum' => $belum,
            'menunggu' => $validasi + $belum,
            'tidak_hadir' => $hitung(fn ($x) => $x?->status === 'tidak_hadir' && $x->status_validasi !== 'menunggu'),
        ];
    }

    public static function shooting(string $tanggal): Collection
    {
        return EventShootingDate::whereDate('tanggal', $tanggal)
            ->with(['castingProject.adminAssignments.user', 'castingProject.shootingDates'])
            ->orderBy('jam_mulai')->get()
            ->filter(fn ($sd) => $sd->castingProject)
            ->each(fn ($sd) => $sd->rekap = self::rekap(self::peserta($sd->castingProject, $sd)));
    }

    public static function shootingTerdekat(): ?EventShootingDate
    {
        return EventShootingDate::whereDate('tanggal', '>', today())->orderBy('tanggal')->first();
    }

    public static function menungguValidasi(int $limit = 10): Collection
    {
        return Attendance::where('status_validasi', 'menunggu')
            ->with(['projectApplication.extras.user', 'projectApplication.castingProject:id,nama_produksi'])
            ->latest()->take($limit)->get();
    }

    public static function catatanTerbaru(int $limit = 5): Collection
    {
        return FieldNote::with(['korlap:id,name', 'projectApplication.extras.user', 'projectApplication.castingProject:id,nama_produksi'])
            ->latest()->take($limit)->get();
    }
}
