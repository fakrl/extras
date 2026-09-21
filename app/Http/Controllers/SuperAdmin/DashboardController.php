<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\EventShootingDate;
use App\Models\StaffPayroll;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    public function index()
    {
        $proyekBerjalan = CastingProject::where('status', 'dibuka')->count();
        $extrasAktif = User::where('role', 'extras')->where('status', 'aktif')->count();
        $totalAkun = User::count();
        $honorBelumDiproses = StaffPayroll::whereNull('generated_at')->count();

        $roleDisplayNames = [
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'korlap' => 'Koordinator Lapangan',
            'client' => 'Client',
            'extras' => 'Extras',
        ];

        $rekapHonorAdmin = User::whereIn('role', ['admin', 'korlap'])
            ->with('adminProjectAssignments.payroll')
            ->get()
            ->map(function (User $admin) use ($roleDisplayNames) {
                $selesai = $admin->adminProjectAssignments->where('status_log', 'selesai');

                return (object) [
                    'nama' => $admin->name,
                    'role' => $roleDisplayNames[$admin->role] ?? ucwords(str_replace('_', ' ', $admin->role)),
                    'total_honor' => $selesai->sum(fn ($a) => $a->payroll?->nominalTotal() ?? 0),
                    'proyek_selesai' => $selesai->count(),
                    'proyek_berjalan' => $admin->adminProjectAssignments->count() - $selesai->count(),
                ];
            })
            ->sortByDesc('total_honor')
            ->take(5)
            ->values();

        $pendingRequests = CastingProject::where('client_request_status', 'menunggu_acc')
            ->with('diajukanOlehClient')
            ->latest()
            ->get();

        $ringkasanProyek = CastingProject::where('status', 'dibuka')
            ->with(['shootingDates' => fn ($q) => $q->orderBy('tanggal')])
            ->get()
            ->sortByDesc(fn ($p) => $p->isUrgent())
            ->take(5)
            ->values();

        $rekapMarginUrl = route('super-admin.recap-margin');

        $jadwalBulanIni = EventShootingDate::whereBetween('tanggal', [
            Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth(),
        ])->with('castingProject:id,nama_produksi')->get()
            ->map(fn ($e) => tap($e, fn ($e) => $e->nama_produksi = $e->castingProject?->nama_produksi));

        return view('super-admin.dashboard', compact(
            'proyekBerjalan',
            'extrasAktif',
            'totalAkun',
            'honorBelumDiproses',
            'rekapHonorAdmin',
            'pendingRequests',
            'ringkasanProyek',
            'rekapMarginUrl',
            'jadwalBulanIni'
        ));
    }

    public function accProject(CastingProject $castingProject): RedirectResponse
    {
        $castingProject->update([
            'client_request_status' => 'disetujui',
            'status' => 'dibuka',
        ]);

        ActivityLog::record(
            'APPROVE_PROJECT_REQUEST',
            "Super Admin menyetujui (ACC) permintaan proyek '{$castingProject->nama_produksi}' dari Client",
            $castingProject
        );

        return back()->with('status', "Permintaan proyek '{$castingProject->nama_produksi}' berhasil disetujui (ACC). Proyek kini masuk antrean Admin.");
    }

    public function rejectProject(CastingProject $castingProject): RedirectResponse
    {
        $castingProject->update([
            'client_request_status' => 'ditolak',
            'status' => 'ditutup',
        ]);

        ActivityLog::record(
            'REJECT_PROJECT_REQUEST',
            "Super Admin menolak permintaan proyek '{$castingProject->nama_produksi}' dari Client",
            $castingProject
        );

        return back()->with('status', "Permintaan proyek '{$castingProject->nama_produksi}' telah ditolak.");
    }
}
