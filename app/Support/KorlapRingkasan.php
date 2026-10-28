<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\CastingProject;
use App\Models\EventShootingDate;
use App\Models\FieldNote;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

// BL.2: satu sumber data absensi, dipakai halaman Absensi & pratinjau Monitoring Korlap SA.
class KorlapRingkasan
{
    public static function peserta(CastingProject $project, EventShootingDate $shootingDate): Collection
    {
        return $project->applications()
            ->whereIn('status_partisipasi', ProjectApplication::STATUS_LOLOS_KE_ATAS)
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

    // BZ.2: $korlapId diisi = hanya proyek penugasan Korlap itu; null = semua (Super Admin).
    private static function penugasan(?int $korlapId): \Closure
    {
        return fn ($q) => $q->where('user_id', $korlapId);
    }

    public static function shooting(string $tanggal, ?int $korlapId = null): Collection
    {
        return EventShootingDate::whereDate('tanggal', $tanggal)
            ->whereHas('castingProject', fn ($q) => $q->where('client_request_status', 'disetujui'))
            ->when($korlapId, fn ($q) => $q->whereHas('castingProject.adminAssignments', self::penugasan($korlapId)))
            ->with(['castingProject.adminAssignments.user', 'castingProject.shootingDates'])
            ->orderBy('jam_mulai')->get()
            ->filter(fn ($sd) => $sd->castingProject)
            ->each(fn ($sd) => $sd->rekap = self::rekap(self::peserta($sd->castingProject, $sd)));
    }

    public static function shootingTerdekat(?int $korlapId = null): ?EventShootingDate
    {
        return EventShootingDate::whereDate('tanggal', '>', today())
            ->whereHas('castingProject', fn ($q) => $q->where('client_request_status', 'disetujui'))
            ->when($korlapId, fn ($q) => $q->whereHas('castingProject.adminAssignments', self::penugasan($korlapId)))
            ->with('castingProject:id,nama_produksi')->orderBy('tanggal')->first();
    }

    public static function menungguValidasi(int $limit = 10, ?int $korlapId = null): Collection
    {
        return Attendance::where('status_validasi', 'menunggu')
            ->when($korlapId, fn ($q) => $q->whereHas('projectApplication.castingProject.adminAssignments', self::penugasan($korlapId)))
            ->with(['projectApplication.extras.user', 'projectApplication.castingProject:id,nama_produksi'])
            ->latest()->take($limit)->get();
    }

    public static function catatanTerbaru(int $limit = 5, ?int $korlapId = null): Collection
    {
        return FieldNote::when($korlapId, fn ($q) => $q->whereHas('projectApplication.castingProject.adminAssignments', self::penugasan($korlapId)))
            ->with(['korlap:id,name', 'projectApplication.extras.user', 'projectApplication.castingProject:id,nama_produksi'])
            ->latest()->take($limit)->get();
    }

    /**
     * CV.1.4: tugas tertunda Korlap, dihitung dari data yang sudah ada (tanpa kolom baru):
     * (a) selfie menunggu validasi > 2 jam; (b) tanggal shooting hari ini/kemarin dengan
     * peserta lolos/kontrak yang belum diabsen (rekap()['belum'], reuse method lama).
     * Dikelompokkan per proyek+jenis (pola CU.7), kunci pengingat 'pg-{korlap}-{proyek}-{jenis}'
     * sama persis formatnya dengan AdminRingkasan::kelompok(), batas 24 jam per kelompok.
     */
    public static function tugasTertunda(Collection $korlaps, ?int $maks = null): Collection
    {
        $selfie = self::selfieMenungguSemua($korlaps)->groupBy('korlap_id');
        $absen = self::absenBelumSemua($korlaps)->groupBy('korlap_id');

        $perKorlap = $korlaps->map(function (User $k) use ($selfie, $absen, $maks) {
            $semua = $selfie->get($k->id, collect())->concat($absen->get($k->id, collect()))
                ->sortBy(fn ($g) => $g['sejak']?->timestamp ?? 0)->values();

            return ['korlap' => $k, 'jumlah' => $semua->count(), 'tampil' => $semua->when($maks, fn ($c) => $c->take($maks))->values()];
        })->filter(fn ($p) => $p['jumlah'] > 0)->values();

        $kunciSemua = $perKorlap->flatMap(fn ($p) => $p['tampil']->map(fn ($g) => "pg-{$p['korlap']->id}-{$g['proyek_id']}-{$g['jenis']}"))->values();
        $notifTerakhir = DatabaseNotification::where('notifiable_type', User::class)
            ->whereIn('notifiable_id', $korlaps->pluck('id'))
            ->where('data->jenis', 'pengingat_sa')
            ->whereIn('data->kunci', $kunciSemua)
            ->get()
            ->groupBy(fn ($n) => $n->data['kunci'] ?? null)
            ->map(fn ($g) => $g->sortByDesc('created_at')->first());

        return $perKorlap->map(function ($p) use ($notifTerakhir) {
            $tugas = $p['tampil']->map(function ($g) use ($p, $notifTerakhir) {
                $kunci = "pg-{$p['korlap']->id}-{$g['proyek_id']}-{$g['jenis']}";
                $terakhir = $notifTerakhir->get($kunci);

                return $g + ['kunci' => $kunci, 'diingatkan_pada' => $terakhir && $terakhir->created_at->gt(now()->subDay()) ? $terakhir->created_at : null];
            })->values();

            return ['korlap' => $p['korlap'], 'jumlah' => $p['jumlah'], 'tugas' => $tugas];
        });
    }

    /** CV N+1 fix: 1 query agregat untuk SEMUA Korlap (bukan loop per-Korlap), lalu di-group per korlap di memori. */
    private static function selfieMenungguSemua(Collection $korlaps): Collection
    {
        $ids = $korlaps->pluck('id');

        $byProyek = Attendance::where('status_validasi', 'menunggu')
            ->where('created_at', '<=', now()->subHours(2))
            ->whereHas('projectApplication.castingProject', fn ($q) => $q->where('client_request_status', 'disetujui')
                ->whereHas('adminAssignments', fn ($q) => $q->whereIn('user_id', $ids)))
            ->with('projectApplication.castingProject.adminAssignments')
            ->get()
            ->groupBy(fn ($a) => $a->projectApplication->casting_project_id);

        return $byProyek->flatMap(function ($grup, $pid) use ($ids) {
            $proyek = $grup->first()->projectApplication->castingProject;

            return $proyek->adminAssignments->pluck('user_id')->intersect($ids)->map(fn ($kid) => [
                'korlap_id' => (int) $kid,
                'proyek_id' => (int) $pid,
                'proyek' => $proyek->nama_produksi,
                'jenis' => 'selfie_menunggu',
                'tugas' => 'Selfie menunggu validasi',
                'jumlah' => $grup->count(),
                'sejak' => $grup->min('created_at'),
                'url' => route('admin.attendance.index', ['project' => $pid], false),
            ]);
        })->values();
    }

    private static function absenBelumSemua(Collection $korlaps): Collection
    {
        $ids = $korlaps->pluck('id');

        $shootingDates = EventShootingDate::whereDate('tanggal', '>=', today()->subDay())
            ->whereDate('tanggal', '<=', today())
            ->whereHas('castingProject', fn ($q) => $q->where('client_request_status', 'disetujui')
                ->whereHas('adminAssignments', fn ($q) => $q->whereIn('user_id', $ids)))
            ->with('castingProject.adminAssignments')
            ->orderByDesc('tanggal')->orderBy('jam_mulai')
            ->get();

        $appsPerProyek = ProjectApplication::whereIn('casting_project_id', $shootingDates->pluck('casting_project_id')->unique())
            ->whereIn('status_partisipasi', ProjectApplication::STATUS_LOLOS_KE_ATAS)
            ->with('attendances')
            ->get()
            ->groupBy('casting_project_id');

        return $shootingDates
            ->each(function ($sd) use ($appsPerProyek) {
                $apps = $appsPerProyek->get($sd->casting_project_id, collect());
                $sd->belum = $apps->filter(fn ($a) => ! $a->attendances->firstWhere('event_shooting_date_id', $sd->id))->count();
            })
            ->filter(fn ($sd) => $sd->belum > 0)
            ->flatMap(fn ($sd) => $sd->castingProject->adminAssignments->pluck('user_id')->intersect($ids)
                ->map(fn ($kid) => ['korlap_id' => (int) $kid, 'sd' => $sd]))
            ->groupBy(fn ($x) => $x['korlap_id'].'-'.$x['sd']->casting_project_id)
            ->map(function ($grup) {
                $sd0 = $grup->first()['sd'];

                return [
                    'korlap_id' => $grup->first()['korlap_id'],
                    'proyek_id' => (int) $sd0->casting_project_id,
                    'proyek' => $sd0->castingProject->nama_produksi,
                    'jenis' => 'absen_belum',
                    'tugas' => 'Peserta belum diabsen',
                    'jumlah' => $grup->sum(fn ($x) => $x['sd']->belum),
                    'sejak' => $grup->min(fn ($x) => $x['sd']->tanggal),
                    'url' => route('admin.attendance.index', ['project' => $sd0->casting_project_id, 'tanggal' => $sd0->id], false),
                ];
            })->values();
    }
}
