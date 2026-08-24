<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CastingProject;
use App\Models\Payment;
use App\Models\ProjectApplication;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Metrik ringkas — sama untuk semua sub-role (Talco/Korlap/Sosmed ikut lihat,
        // aksinya yang dibatasi lewat middleware role:admin_default di routes).
        $proyekAktif = CastingProject::where('status', 'dibuka')->count();
        $totalPendaftar = ProjectApplication::count();
        $perluDinego = ProjectApplication::where('status_partisipasi', 'nego_fee')->count();

        // Chart 1 (bar horizontal): funnel status partisipasi kandidat
        $statusPartisipasi = ProjectApplication::selectRaw('status_partisipasi, count(*) as total')
            ->groupBy('status_partisipasi')
            ->pluck('total', 'status_partisipasi');

        $partisipasiLabels = [
            'diajukan' => 'Diajukan',
            'direview_admin' => 'Direview Admin',
            'nego_fee' => 'Nego Fee',
            'deal' => 'Deal',
            'diajukan_ke_cd' => 'Diajukan ke CD',
            'direview_cd' => 'Direview CD',
            'lolos' => 'Lolos',
            'ditolak' => 'Ditolak',
            'kontrak_ditandatangani' => 'Kontrak TTD',
            'selesai_produksi' => 'Selesai Produksi',
            'dibatalkan' => 'Dibatalkan',
        ];

        $chartStatusPartisipasi = [
            'labels' => array_values($partisipasiLabels),
            'data' => array_map(fn ($key) => (int) ($statusPartisipasi[$key] ?? 0), array_keys($partisipasiLabels)),
        ];

        // Chart 2 (donut): status pembayaran Extras
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

        return view('admin.dashboard', compact(
            'proyekAktif',
            'totalPendaftar',
            'perluDinego',
            'chartStatusPartisipasi',
            'chartStatusPembayaran'
        ));
    }
}
