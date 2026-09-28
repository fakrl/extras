<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\EventShootingDate;
use App\Models\User;
use App\Notifications\InAppNotification;
use App\Services\KeuanganService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected KeuanganService $keuanganService
    ) {}

    private function periodDates(string $period): array
    {
        $now = Carbon::now();
        $end = $now->copy()->endOfDay();

        $start = match ($period) {
            '7d' => $now->copy()->subDays(6)->startOfDay(),
            '1y' => $now->copy()->subYear()->startOfDay(),
            default => $now->copy()->subDays(29)->startOfDay(), // 30d
        };

        $length = $start->diffInDays($end) + 1;
        $prevEnd = $start->copy()->subDay()->endOfDay();
        $prevStart = $prevEnd->copy()->subDays($length - 1)->startOfDay();

        return compact('start', 'end', 'prevStart', 'prevEnd');
    }

    private function trendBadge(int|float $current, int|float $previous): ?array
    {
        if ($current === 0 && $previous === 0) {
            return null;
        }
        if ($previous === 0 && $current > 0) {
            return ['label' => '+baru', 'up' => true];
        }
        $pct = ($current - $previous) / $previous * 100;

        return ['label' => ($pct >= 0 ? '+' : '').round($pct, 1).'%', 'up' => $pct >= 0];
    }

    public function index(Request $request)
    {
        $period = in_array($request->query('period'), ['7d', '30d', '1y']) ? $request->query('period') : '30d';
        ['start' => $start, 'end' => $end, 'prevStart' => $prevStart, 'prevEnd' => $prevEnd] = $this->periodDates($period);

        // Metric: Proyek Berjalan (proyek dibuka, created_at dalam period)
        $proyekBerjalan = CastingProject::where('status', 'dibuka')->count();
        // ponytail: skip trend proyekBerjalan — "dibuka" adalah status realtime, bukan time-series; period filter tidak relevan

        // Metric: Extras Aktif (semua waktu, tapi trend dihitung dari created_at periode)
        $extrasAktif = User::where('role', 'extras')->where('status', 'aktif')->count();
        $extrasAktifPrev = User::where('role', 'extras')->where('status', 'aktif')
            ->whereBetween('created_at', [$prevStart, $prevEnd])->count();
        $extrasAktifCurr = User::where('role', 'extras')->where('status', 'aktif')
            ->whereBetween('created_at', [$start, $end])->count();
        $trendExtrasAktif = $this->trendBadge($extrasAktifCurr, $extrasAktifPrev);

        // Metric: Total Akun
        $totalAkun = User::count();
        $totalAkunCurr = User::whereBetween('created_at', [$start, $end])->count();
        $totalAkunPrev = User::whereBetween('created_at', [$prevStart, $prevEnd])->count();
        $trendTotalAkun = $this->trendBadge($totalAkunCurr, $totalAkunPrev);

        // Metric: Honor Belum Diproses (SPEC AY.1.15 — single source of truth KeuanganService)
        $honorBelumDiproses = $this->keuanganService->totalHonorStafBelumDiproses();
        // ponytail: skip trend honorBelumDiproses — StaffPayroll tidak punya kolom waktu yang cocok untuk period filter ini

        // AT.2 & AV.6: Margin bulan ini (Single Source of Truth)
        $marginBulanIni = $this->keuanganService->marginBulanIni();

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

        $chartStatusProyek = [
            'labels' => ['Dibuka', 'Ditutup'],
            'data' => [
                CastingProject::where('status', 'dibuka')->count(),
                CastingProject::where('status', 'ditutup')->count(),
            ],
        ];

        $chartMarginBulanan = $this->keuanganService->trendMarginBulanan();

        return view('super-admin.dashboard', compact(
            'period',
            'proyekBerjalan',
            'extrasAktif',
            'totalAkun',
            'honorBelumDiproses',
            'trendExtrasAktif',
            'trendTotalAkun',
            'marginBulanIni',
            'rekapHonorAdmin',
            'pendingRequests',
            'ringkasanProyek',
            'rekapMarginUrl',
            'jadwalBulanIni',
            'chartStatusProyek',
            'chartMarginBulanan'
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

        $castingProject->loadMissing('admin');
        $admin = $castingProject->admin;
        if ($admin) {
            $judulAcc = 'Proyek Baru Disetujui';
            $pesanAcc = "Permintaan proyek '{$castingProject->nama_produksi}' telah disetujui Super Admin. Silakan lengkapi detail proyek.";
            try {
                $admin->notify(new InAppNotification($judulAcc, $pesanAcc, route('admin.projects.edit', $castingProject)));
            } catch (\Throwable) {
            }
        }

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
