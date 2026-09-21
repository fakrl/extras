<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AdminProjectAssignment;
use App\Models\Attendance;
use App\Models\CastingProject;
use App\Models\ProjectApplication;
use App\Models\User;

class MonitoringController extends Controller
{
    public function index()
    {
        $extrasAktif = User::where('role', 'extras')->where('status', 'aktif')->count();
        $extrasTotal = User::where('role', 'extras')->count();
        $cdTotal = User::where('role', 'client')->count();
        $adminTotal = User::whereIn('role', ['admin', 'korlap'])->count();

        $extrasList = User::where('role', 'extras')->with('extrasProfile.user:id,username')->latest()->get();
        $cdList = User::where('role', 'client')->latest()->get();

        $jadwalProjects = CastingProject::whereHas('shootingDates', fn ($q) => $q->whereNotNull('lokasi'))
            ->with(['shootingDates' => fn ($q) => $q->whereNotNull('lokasi')->orderBy('tanggal')])
            ->latest()
            ->get();

        $recentAttendances = Attendance::with([
            'projectApplication.extras.user',
            'projectApplication.castingProject',
            'eventShootingDate',
            'divalidasiOleh',
            'dicatatOleh',
        ])->latest()->take(15)->get();

        $attendanceStats = [
            'total_hadir' => Attendance::where('status', 'hadir')->count(),
            'menunggu_validasi' => Attendance::where('status_validasi', 'menunggu')->count(),
            'tervalidasi' => Attendance::where('status_validasi', 'tervalidasi')->count(),
        ];

        $akunPerRole = User::selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role');
        $roleLabels = [
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'korlap' => 'Koordinator Lapangan',
            'client' => 'Client/Casting Director',
            'extras' => 'Extras',
        ];
        $chartAkunPerRole = [
            'labels' => array_values($roleLabels),
            'data' => array_map(fn ($key) => (int) ($akunPerRole[$key] ?? 0), array_keys($roleLabels)),
        ];

        $statusExtras = User::where('role', 'extras')->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $chartStatusExtras = [
            'labels' => ['Aktif', 'Nonaktif'],
            'data' => [(int) ($statusExtras['aktif'] ?? 0), (int) ($statusExtras['nonaktif'] ?? 0)],
        ];

        $statusPartisipasi = ProjectApplication::selectRaw('status_partisipasi, count(*) as total')->groupBy('status_partisipasi')->pluck('total', 'status_partisipasi');
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

        $assignmentSelesai = AdminProjectAssignment::where('status_log', 'selesai')->count();
        $assignmentTotal = AdminProjectAssignment::count();

        return view('super-admin.monitoring', compact(
            'extrasAktif', 'extrasTotal', 'cdTotal', 'adminTotal', 'extrasList', 'cdList', 'jadwalProjects',
            'recentAttendances', 'attendanceStats',
            'chartAkunPerRole', 'chartStatusExtras', 'chartStatusPartisipasi',
            'assignmentSelesai', 'assignmentTotal'
        ));
    }
}
