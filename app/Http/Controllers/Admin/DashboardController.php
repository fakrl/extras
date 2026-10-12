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

        // AD-09: default hanya proyek milik Admin ini (PIC); toggle "Semua" untuk lihat seluruh proyek.
        $semua = request()->boolean('semua');
        $pic = ! $semua && $user->isAdmin() ? $user->id : null;
        $proyekPic = fn ($q) => $q->when($pic, fn ($q) => $q->where('admin_id', $pic));
        $lamaranPic = fn ($q) => $q->when($pic, fn ($q) => $q->whereHas('castingProject', fn ($p) => $p->where('admin_id', $pic)));
        $bayarPic = fn ($q) => $q->when($pic, fn ($q) => $q->whereHas('projectApplication.castingProject', fn ($p) => $p->where('admin_id', $pic)));

        $proyekAktif = CastingProject::where('status', 'dibuka')->tap($proyekPic)->count();
        $totalPendaftar = ProjectApplication::query()->tap($lamaranPic)->count();
        $perluDinego = ProjectApplication::where('status_partisipasi', 'nego_fee')->tap($lamaranPic)->count();

        $statusPembayaran = Payment::selectRaw('status, count(*) as total')
            ->tap($bayarPic)
            ->groupBy('status')
            ->pluck('total', 'status');

        $chartStatusPembayaran = [
            'labels' => ['Perlu Ditransfer', 'Ditransfer', 'Dikonfirmasi Diterima', 'Menunggu Kontrak'],
            'data' => [
                $perluDitransfer = Payment::perluDitransfer()->tap($bayarPic)->count(),
                (int) ($statusPembayaran['ditransfer'] ?? 0),
                (int) ($statusPembayaran['dikonfirmasi_diterima'] ?? 0),
                (int) ($statusPembayaran['belum_dibayar'] ?? 0) - $perluDitransfer,
            ],
        ];

        $sengketa = Payment::where('status', 'disengketakan')->tap($bayarPic)->get();
        $pembayaranSengketa = $sengketa->count();
        $linkSengketa = $pembayaranSengketa === 1
            ? route('payments.show', $sengketa->first()->project_application_id)
            : route('admin.projects.index', ['bayar' => 'sengketa']);
        $ringkasan = AdminRingkasan::untuk($pic);
        $tahapan = AdminRingkasan::tahapan($pic);

        $urgentProjects = CastingProject::where('status', 'dibuka')->tap($proyekPic)
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
            'linkSengketa',
            'semua',
            'ringkasan',
            'tahapan'
        ));
    }
}
