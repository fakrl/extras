<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CastingProject;
use App\Models\EventShootingDate;
use App\Models\Payment;
use App\Models\ProjectApplication;
use App\Support\AdminRingkasan;
use App\Support\KorlapRingkasan;
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

        $statusPembayaran = Payment::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $chartStatusPembayaran = [
            'labels' => ['Perlu Ditransfer', 'Ditransfer', 'Dikonfirmasi Diterima', 'Menunggu Kontrak'],
            'data' => [
                $perluDitransfer = Payment::perluDitransfer()->count(),
                (int) ($statusPembayaran['ditransfer'] ?? 0),
                (int) ($statusPembayaran['dikonfirmasi_diterima'] ?? 0),
                (int) ($statusPembayaran['belum_dibayar'] ?? 0) - $perluDitransfer,
            ],
        ];

        $pembayaranSengketa = Payment::where('status', 'disengketakan')->count();
        $ringkasan = AdminRingkasan::untuk();
        $tahapan = AdminRingkasan::tahapan();

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

        $korlap = [];
        if ($user->bisaSebagaiKorlap() && ! ($user->bisaSebagaiAdmin() && $user->modeSa() !== 'korlap')) {
            $id = $user->isKorlap() ? $user->id : null;
            $punyaTugas = ! $id || $user->adminProjectAssignments()->exists();
            $hari = ['Hari ini' => KorlapRingkasan::shooting(today()->toDateString(), $id), 'Besok' => KorlapRingkasan::shooting(today()->addDay()->toDateString(), $id)];
            $korlap = [
                'punyaTugas' => $punyaTugas,
                'hari' => $hari,
                'terdekat' => $hari['Hari ini']->isEmpty() ? KorlapRingkasan::shootingTerdekat($id) : null,
                'menunggu' => KorlapRingkasan::menungguValidasi(10, $id),
                'catatan' => KorlapRingkasan::catatanTerbaru(5, $id),
            ];
        }

        return view('admin.dashboard', $korlap + compact(
            'proyekAktif',
            'totalPendaftar',
            'perluDinego',
            'chartStatusPembayaran',
            'urgentProjects',
            'jadwalBulanIni',
            'pembayaranSengketa',
            'ringkasan',
            'tahapan'
        ));
    }
}
