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

        // CM.2.3: dashboard Admin/Korlap selalu hanya proyek yang dikelola, tanpa toggle "Semua".
        $proyekPic = fn ($q) => $q->dikelolaOleh($user);
        $lamaranPic = fn ($q) => $q->whereHas('castingProject', fn ($p) => $p->dikelolaOleh($user));
        $bayarPic = fn ($q) => $q->whereHas('projectApplication.castingProject', fn ($p) => $p->dikelolaOleh($user));

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
        $ringkasan = AdminRingkasan::untuk($user);
        $tahapan = $this->tandaiDiingatkan(AdminRingkasan::tahapan($user), $user);

        $urgentProjects = CastingProject::where('status', 'dibuka')->tap($proyekPic)
            ->with(['shootingDates', 'applications', 'client'])
            ->get()
            ->filter(fn ($p) => $p->isUrgent())
            ->values();

        $proyekIds = CastingProject::query()->dikelolaOleh($user)->pluck('id');
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
            'ringkasan',
            'tahapan'
        ));
    }

    /** CO.2.3: tandai item tahapan yang punya pengingat SA belum dibaca ('diingatkan'). */
    private function tandaiDiingatkan(array $tahapan, $user): array
    {
        $kunci = $user->unreadNotifications()->where('data->jenis', 'pengingat_sa')->get()
            ->map(fn ($n) => $n->data['kunci'] ?? null)->filter()->flip();

        return collect($tahapan)->map(function ($t) use ($kunci) {
            $t['daftar'] = collect($t['daftar'])->map(function ($i) use ($kunci) {
                $i['diingatkan'] = $kunci->has('pa-'.$i['app']->id);

                return $i;
            })->values();

            return $t;
        })->all();
    }
}
