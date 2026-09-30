<?php

namespace App\Http\Controllers\Extras;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\CastingProject;
use App\Models\EventShootingDate;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $extrasProfile = $user->extrasProfile;

        $pendaftaranSaya = $extrasProfile
            ? ProjectApplication::where('extras_id', $extrasProfile->id)
                ->with(['castingProject.shootingDates', 'payment:id,project_application_id,status'])
                ->latest()
                ->get()
                ->unique('casting_project_id')
            : collect();

        // BK.3: selesai/ditolak/batal ke Riwayat (kecuali honor masih menunggu konfirmasi); sisanya urut tanggal shooting terdekat.
        $hariIni = now()->toDateString();
        [$riwayatPendaftaran, $pendaftaranAktif] = $pendaftaranSaya->partition(fn ($a) => in_array($a->status_partisipasi, ['selesai_produksi', 'ditolak', 'dibatalkan'], true)
            && $a->payment?->status !== 'ditransfer');
        $pendaftaranAktif = $pendaftaranAktif->sortBy(function ($a) use ($hariIni) {
            $tanggal = $a->castingProject->shootingDates->map(fn ($d) => $d->tanggal->toDateString());
            $mendatang = $tanggal->filter(fn ($t) => $t >= $hariIni)->min();

            return $mendatang ? '0'.$mendatang : ($tanggal->isNotEmpty() ? '1'.$tanggal->max() : '2');
        })->values();

        $aktivitasSaya = collect();
        if ($extrasProfile) {
            $aktivitasSaya = ActivityLog::where(function ($q) use ($extrasProfile) {
                $q->where('user_id', Auth::id())
                    ->orWhere(function ($sq) use ($extrasProfile) {
                        $sq->where('subject_type', ExtrasProfile::class)
                            ->where('subject_id', $extrasProfile->id);
                    });
            })->latest('created_at')->take(5)->get();
        }

        $proyekLolos = $extrasProfile
            ? ProjectApplication::where('extras_id', $extrasProfile->id)
                ->whereIn('status_partisipasi', ProjectApplication::STATUS_LOLOS_KE_ATAS)
                ->pluck('casting_project_id')
            : collect();

        $jadwalBulanIni = EventShootingDate::whereIn('casting_project_id', $proyekLolos)
            ->whereBetween('tanggal', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->with('castingProject:id,nama_produksi')
            ->get()
            ->map(fn ($e) => tap($e, fn ($e) => $e->nama_produksi = $e->castingProject?->nama_produksi));

        // BJ.4: lowongan yang belum didaftar; peran paling cocok yang masih ada slot, urut % cocok desc lalu deadline terdekat.
        $tagSaya = $extrasProfile?->categories->modelKeys() ?? [];
        $castingCallTerbuka = CastingProject::lowonganTerbuka()
            ->whereNotIn('id', $pendaftaranSaya->pluck('casting_project_id'))
            ->each(function ($p) use ($tagSaya) {
                $p->setRelation('peranCocok', $p->classes->sortByDesc(fn ($c) => [$c->sisaKuota() > 0, $c->persenCocok($tagSaya) ?? -1])->first());
                $p->persenCocok = $p->peranCocok?->persenCocok($tagSaya);
            })
            ->sortBy([fn ($a, $b) => ($b->persenCocok ?? -1) <=> ($a->persenCocok ?? -1), fn ($a, $b) => $a->deadline <=> $b->deadline])
            ->take(5)
            ->values();

        $riwayatAbsensi = $extrasProfile
            ? Attendance::whereHas('projectApplication', fn ($q) => $q->where('extras_id', $extrasProfile->id))
                ->with(['projectApplication.castingProject:id,nama_produksi', 'eventShootingDate:id,tanggal'])
                ->latest()
                ->take(5)
                ->get()
            : collect();

        return view('extras.dashboard', compact('extrasProfile', 'pendaftaranAktif', 'riwayatPendaftaran', 'aktivitasSaya', 'jadwalBulanIni', 'castingCallTerbuka', 'riwayatAbsensi'));
    }
}
