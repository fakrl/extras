<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CastingProject;
use App\Models\EventShootingDate;
use App\Models\Payment;
use App\Models\ProjectApplication;
use App\Support\AdminRingkasan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $proyekAktif = CastingProject::where('status', 'dibuka')->count();
        $totalPendaftar = ProjectApplication::count();
        $perluDinego = ProjectApplication::where('status_partisipasi', 'nego_fee')->count();

        $statusPartisipasi = ProjectApplication::selectRaw('status_partisipasi, count(*) as total')
            ->groupBy('status_partisipasi')
            ->pluck('total', 'status_partisipasi');

        // satu sumber label (AY.3.4), jangan map lokal
        $partisipasiLabels = ProjectApplication::LABELS;

        $chartStatusPartisipasi = [
            'labels' => array_values($partisipasiLabels),
            'data' => array_map(fn ($key) => (int) ($statusPartisipasi[$key] ?? 0), array_keys($partisipasiLabels)),
        ];

        $statusPembayaran = Payment::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $chartStatusPembayaran = [
            'labels' => ['Belum Dibayar', 'Ditransfer', 'Dikonfirmasi Diterima'],
            'data' => [
                (int) ($statusPembayaran['belum_dibayar'] ?? 0),
                (int) ($statusPembayaran['ditransfer'] ?? 0),
                (int) ($statusPembayaran['dikonfirmasi_diterima'] ?? 0),
            ],
        ];

        $pembayaranSengketa = Payment::where('status', 'disengketakan')->count();
        $ringkasan = AdminRingkasan::untuk();

        $urgentProjects = CastingProject::where('status', 'dibuka')
            ->with(['shootingDates', 'applications', 'client'])
            ->get()
            ->filter(fn ($p) => $p->isUrgent())
            ->values();

        $proyekIds = $user->adminProjectAssignments()->pluck('casting_project_id');
        $jadwalBulanIni = EventShootingDate::whereIn('casting_project_id', $proyekIds)
            ->whereBetween('tanggal', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->with('castingProject:id,nama_produksi')
            ->get()
            ->map(fn ($e) => tap($e, fn ($e) => $e->nama_produksi = $e->castingProject?->nama_produksi));

        return view('admin.dashboard', compact(
            'proyekAktif',
            'totalPendaftar',
            'perluDinego',
            'chartStatusPartisipasi',
            'chartStatusPembayaran',
            'urgentProjects',
            'jadwalBulanIni',
            'pembayaranSengketa',
            'ringkasan'
        ));
    }
}
