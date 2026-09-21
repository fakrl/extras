<?php

namespace App\Http\Controllers\Cd;

use App\Http\Controllers\Controller;
use App\Models\CastingProject;
use App\Models\CdProjectAssignment;
use App\Models\CdReview;
use App\Models\EventShootingDate;
use App\Models\Payment;
use App\Models\ProjectApplication;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $cdId = Auth::id();

        $proyekIds = CdProjectAssignment::where('cd_user_id', $cdId)->pluck('casting_project_id');

        $perluDireview = ProjectApplication::where('status_partisipasi', 'diajukan_ke_cd')
            ->whereIn('casting_project_id', $proyekIds)
            ->count();

        $approve = CdReview::where('cd_id', $cdId)->where('keputusan', 'approve')->count();
        $reject = CdReview::where('cd_id', $cdId)->where('keputusan', 'reject')->count();

        $chartKeputusan = [
            'labels' => ['Approve', 'Reject'],
            'data' => [$approve, $reject],
        ];

        $proyekBerjalan = CastingProject::whereIn('id', $proyekIds)
            ->where('status', 'dibuka')
            ->get(['id', 'nama_produksi', 'deadline']);

        $pelunasanPending = Payment::whereIn('status', ['belum_dibayar', 'ditransfer'])
            ->whereHas('projectApplication', fn ($q) => $q->whereIn('casting_project_id', $proyekIds))
            ->with('projectApplication.castingProject:id,nama_produksi')
            ->get()
            ->groupBy(fn ($p) => $p->projectApplication->castingProject->nama_produksi)
            ->map(fn ($group) => $group->count());

        $karakterPendaftar = ProjectApplication::whereIn('casting_project_id', $proyekIds)
            ->whereIn('status_partisipasi', [
                'diajukan_ke_cd', 'lolos', 'kontrak_ditandatangani', 'selesai_produksi', 'ditolak',
            ])
            ->with('castingProjectClass:id,nama_kelas', 'castingProject:id,nama_produksi')
            ->get()
            ->groupBy(fn ($app) => $app->casting_project_id.'|'.($app->castingProjectClass->id ?? 0))
            ->map(fn ($group) => [
                'proyek' => $group->first()->castingProject->nama_produksi,
                'karakter' => $group->first()->castingProjectClass->nama_kelas ?? '-',
                'jumlah' => $group->count(),
            ])
            ->values();

        $jadwalBulanIni = EventShootingDate::whereIn('casting_project_id', $proyekIds)
            ->whereBetween('tanggal', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->with('castingProject:id,nama_produksi')
            ->get()
            ->map(fn ($e) => tap($e, fn ($e) => $e->nama_produksi = $e->castingProject?->nama_produksi));

        return view('cd.dashboard', compact(
            'perluDireview', 'chartKeputusan', 'proyekBerjalan', 'pelunasanPending', 'karakterPendaftar',
            'jadwalBulanIni'
        ));
    }
}
