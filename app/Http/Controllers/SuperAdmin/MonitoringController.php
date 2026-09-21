<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\CastingProject;
use App\Models\User;

/**
 * RF-50 (diperluas): dashboard monitoring Super Admin mencakup ringkasan
 * SEMUA akun sistem (5 roles) dan monitoring absensi lapangan real-time.
 */
class MonitoringController extends Controller
{
    public function index()
    {
        $extrasAktif = User::where('role', 'extras')->where('status', 'aktif')->count();
        $extrasTotal = User::where('role', 'extras')->count();
        $cdTotal = User::whereIn('role', ['client', 'casting_director'])->count();
        $adminTotal = User::whereIn('role', ['admin', 'admin_default', 'korlap', 'admin_korlap'])->count();

        $extrasList = User::where('role', 'extras')->with('extrasProfile.user:id,username')->latest()->get();
        $cdList = User::whereIn('role', ['client', 'casting_director'])->latest()->get();

        // Jadwal read-only: proyek yang punya shooting dates dengan jadwal terisi
        $jadwalProjects = CastingProject::whereHas('shootingDates', fn ($q) => $q->whereNotNull('lokasi'))
            ->with(['shootingDates' => fn ($q) => $q->whereNotNull('lokasi')->orderBy('tanggal')])
            ->latest()
            ->get();

        // Monitoring Absensi Lapangan Realtime untuk Super Admin
        $recentAttendances = Attendance::with([
            'projectApplication.extras.user',
            'projectApplication.castingProject',
            'eventShootingDate',
            'divalidasiOleh',
            'dicatatOleh',
        ])
            ->latest()
            ->take(15)
            ->get();

        $attendanceStats = [
            'total_hadir' => Attendance::where('status', 'hadir')->count(),
            'menunggu_validasi' => Attendance::where('status_validasi', 'menunggu')->count(),
            'tervalidasi' => Attendance::where('status_validasi', 'tervalidasi')->count(),
        ];

        return view('super-admin.monitoring', compact(
            'extrasAktif', 'extrasTotal', 'cdTotal', 'adminTotal', 'extrasList', 'cdList', 'jadwalProjects',
            'recentAttendances', 'attendanceStats'
        ));
    }
}
