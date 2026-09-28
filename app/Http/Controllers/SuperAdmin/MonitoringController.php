<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AdminProjectAssignment;
use App\Models\Attendance;
use App\Models\EventShootingDate;
use App\Models\User;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    public function index(Request $request)
    {
        $extrasAktif = User::where('role', 'extras')->where('status', 'aktif')->count();
        $extrasTotal = User::where('role', 'extras')->count();
        $cdTotal = User::where('role', 'client')->count();
        $adminTotal = User::whereIn('role', ['admin', 'korlap'])->count();

        $shootingDates = EventShootingDate::with('castingProject:id,nama_produksi')
            ->orderBy('tanggal')
            ->get()
            ->map(fn ($d) => (object) [
                'tanggal' => $d->tanggal,
                'lokasi' => $d->lokasi,
                'jam_mulai' => $d->jam_mulai,
                'jam_selesai' => $d->jam_selesai,
                'catatan' => $d->catatan,
                'nama_produksi' => $d->castingProject?->nama_produksi,
            ]);

        $absensiPerProyek = Attendance::with([
            'projectApplication.extras.user',
            'projectApplication.castingProject',
            'eventShootingDate',
            'divalidasiOleh',
        ])
            ->latest()
            ->take(60)
            ->get()
            ->groupBy(fn ($a) => $a->projectApplication?->castingProject?->nama_produksi ?? 'Tanpa Proyek');

        $attendanceStats = [
            'total_hadir' => Attendance::where('status', 'hadir')->count(),
            'menunggu_validasi' => Attendance::where('status_validasi', 'menunggu')->count(),
            'tervalidasi' => Attendance::where('status_validasi', 'tervalidasi')->count(),
        ];

        $assignmentSelesai = AdminProjectAssignment::where('status_log', 'selesai')->count();
        $assignmentTotal = AdminProjectAssignment::count();

        $unifiedSearch = $request->input('q');
        $unifiedType = $request->input('type', 'all');

        $query = User::query();

        if ($unifiedType !== 'all') {
            $roleMap = [
                'extras' => ['extras'],
                'client' => ['client'],
                'admin' => ['admin', 'korlap'],
            ];
            $query->whereIn('role', $roleMap[$unifiedType] ?? []);
        } else {
            $query->whereNotIn('role', ['super_admin']);
        }

        if ($unifiedSearch) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$unifiedSearch}%")
                ->orWhere('email', 'like', "%{$unifiedSearch}%")
                ->orWhere('username', 'like', "%{$unifiedSearch}%")
            );
        }

        $unifiedResults = $query->with('extrasProfile:user_id,grade_saat_ini')
            ->latest()
            ->limit(30)
            ->get();

        return view('super-admin.monitoring', compact(
            'extrasAktif', 'extrasTotal', 'cdTotal', 'adminTotal',
            'shootingDates', 'absensiPerProyek', 'attendanceStats',
            'assignmentSelesai', 'assignmentTotal',
            'unifiedResults', 'unifiedSearch', 'unifiedType'
        ));
    }
}
