<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AdminProjectAssignment;
use App\Models\CastingProject;
use App\Models\ProjectApplication;
use App\Models\StaffPayroll;
use App\Models\User;

/**
 * RF-50: dashboard monitoring Super Admin — read-only, ringkasan operasional
 * + ringkasan seluruh akun sistem. Paling banyak chart di antara semua role
 * (keputusan Fakrul 24 Agu 2026) karena ini satu-satunya role yang punya
 * visibilitas lintas seluruh sistem.
 */
class DashboardController extends Controller
{
    public function index()
    {
        // Metrik ringkas atas
        $proyekBerjalan = CastingProject::where('status', 'dibuka')->count();
        $extrasAktif = User::where('role', 'extras')->where('status', 'aktif')->count();
        $totalAkun = User::count();
        $honorBelumDiproses = StaffPayroll::whereNull('generated_at')->count();

        // Chart 1 (bar): jumlah akun per role
        $akunPerRole = User::selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $roleLabels = [
            'super_admin' => 'Super Admin',
            'admin_default' => 'Admin Default',
            'admin_talco' => 'Admin Talco',
            'admin_korlap' => 'Admin Korlap',
            'admin_sosmed' => 'Admin Sosmed',
            'casting_director' => 'Casting Director',
            'extras' => 'Extras',
        ];

        $chartAkunPerRole = [
            'labels' => array_values($roleLabels),
            'data' => array_map(fn ($key) => (int) ($akunPerRole[$key] ?? 0), array_keys($roleLabels)),
        ];

        // Chart 2 (donut): status keaktifan Extras
        $statusExtras = User::where('role', 'extras')
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $chartStatusExtras = [
            'labels' => ['Aktif', 'Nonaktif'],
            'data' => [(int) ($statusExtras['aktif'] ?? 0), (int) ($statusExtras['nonaktif'] ?? 0)],
        ];

        // Chart 3 (bar horizontal): funnel status partisipasi kandidat
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

        // Progress bar: penugasan admin selesai vs berjalan
        $assignmentSelesai = AdminProjectAssignment::where('status_log', 'selesai')->count();
        $assignmentTotal = AdminProjectAssignment::count();

        return view('super-admin.dashboard', compact(
            'proyekBerjalan',
            'extrasAktif',
            'totalAkun',
            'honorBelumDiproses',
            'chartAkunPerRole',
            'chartStatusExtras',
            'chartStatusPartisipasi',
            'assignmentSelesai',
            'assignmentTotal'
        ));
    }
}
