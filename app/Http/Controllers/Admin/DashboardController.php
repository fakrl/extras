<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CastingProject;
use App\Models\EventShootingDate;
use App\Models\Payment;
use App\Models\ProjectApplication;
use App\Support\AdminRingkasan;
use App\Support\KorlapRingkasan;
use App\Support\Timeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // CM.2.3: dashboard Admin/Korlap selalu hanya proyek yang dikelola, tanpa toggle "Semua".
        $proyekPic = fn ($q) => $q->dikelolaOleh($user);
        $lamaranPic = fn ($q) => $q->whereHas('castingProject', fn ($p) => $p->dikelolaOleh($user));
        $bayarPic = fn ($q) => $q->whereHas('projectApplication.castingProject', fn ($p) => $p->dikelolaOleh($user));

        $proyekAktif = CastingProject::where('status', 'dibuka')->tap($proyekPic)->count();
        $totalPendaftar = ProjectApplication::query()->tap($lamaranPic)->count();
        $perluDinego = ProjectApplication::where('status_partisipasi', 'nego_fee')->tap($lamaranPic)->count();

        // Rincian Breakdown Hover Proyek & Pendaftar
        // Tahap proyek dari scopeDiTahap(), sama persis dengan kartu Ekosistem Proyek Super Admin.
        $totalProyekAktifAgency = CastingProject::where('status', 'dibuka')->count();
        $proyekLowonganSeleksi = CastingProject::diTahap('mendatang')->tap($proyekPic)->count();
        $proyekShooting = CastingProject::diTahap('berjalan')->tap($proyekPic)->count();

        $pendaftarMenungguReview = ProjectApplication::whereIn('status_partisipasi', ['diajukan', 'menunggu_review'])->tap($lamaranPic)->count();
        $pendaftarReviewClient = ProjectApplication::whereIn('status_partisipasi', ['deal', 'diajukan_ke_client'])->tap($lamaranPic)->count();
        $pendaftarBelumKontrak = ProjectApplication::where('status_partisipasi', 'lolos')->tap($lamaranPic)->count();

        $breakdownAdmin = [
            'proyek_pic' => $proyekAktif,
            'proyek_agency' => $totalProyekAktifAgency,
            'proyek_lowongan_seleksi' => $proyekLowonganSeleksi,
            'proyek_shooting' => $proyekShooting,
            'pendaftar_review' => $pendaftarMenungguReview,
            'pendaftar_nego' => $perluDinego,
            'pendaftar_client' => $pendaftarReviewClient,
            'pendaftar_kontrak' => $pendaftarBelumKontrak,
        ];

        // Bottleneck Kuota Proyek Aktif
        $proyekBottleneck = CastingProject::where('status', 'dibuka')
            ->tap($proyekPic)
            ->with(['classes', 'shootingDates', 'applications'])
            ->get()
            ->map(function ($p) {
                $target = (int) $p->classes->sum('kuota_kelas');
                $terisi = (int) $p->applications->filter(fn ($a) => in_array($a->status_partisipasi, ['deal', 'diajukan_ke_client', 'lolos', 'kontrak_ditandatangani', 'selesai_produksi'], true))->count();
                $persen = $target > 0 ? (int) round(($terisi / $target) * 100) : 100;
                $p->target_kuota = $target;
                $p->terisi_kuota = $terisi;
                $p->persen_kuota = $persen;

                return $p;
            })
            ->filter(fn ($p) => $p->target_kuota > 0 && $p->persen_kuota < 100)
            ->sortBy('persen_kuota')
            ->values();

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
        // CR.1.3: timeline Jadwal default Minggu ini untuk semua (Admin & Korlap sama-sama).
        [$dari, $sampai, $skala] = Timeline::dariSesi($request, 'timeline_admin_dashboard', 'minggu');
        $agendaEvents = EventShootingDate::whereIn('casting_project_id', $proyekIds)
            ->whereBetween('tanggal', [$dari, $sampai])
            ->with('castingProject:id,nama_produksi')
            ->get()
            ->each(fn ($e) => $e->nama_produksi = $e->castingProject?->nama_produksi);

        $korlap = [];
        if ($user->bisaSebagaiKorlap() && ! ($user->bisaSebagaiAdmin() && $user->modeSa() !== 'korlap')) {
            $id = $user->isKorlap() ? $user->id : null;
            $punyaTugas = ! $id || $user->adminProjectAssignments()->exists();
            $hari = ['Hari ini' => KorlapRingkasan::shooting(today()->toDateString(), $id), 'Besok' => KorlapRingkasan::shooting(today()->addDay()->toDateString(), $id)];

            $semuaJadwalAktif = $hari['Hari ini']->concat($hari['Besok']);
            $totalPanggilan = (int) $semuaJadwalAktif->sum(fn ($sd) => $sd->rekap['total'] ?? 0);
            $totalHadir = (int) $semuaJadwalAktif->sum(fn ($sd) => $sd->rekap['hadir'] ?? 0);
            $totalMenungguVal = (int) $semuaJadwalAktif->sum(fn ($sd) => $sd->rekap['menunggu_validasi'] ?? 0);
            $totalTidakHadir = (int) $semuaJadwalAktif->sum(fn ($sd) => $sd->rekap['tidak_hadir'] ?? 0);
            $totalBelumAbsen = (int) $semuaJadwalAktif->sum(fn ($sd) => $sd->rekap['belum'] ?? 0);
            $tingkatKehadiran = $totalPanggilan > 0 ? (int) round(($totalHadir / $totalPanggilan) * 100) : 0;

            $menungguValidasiList = KorlapRingkasan::menungguValidasi(10, $id);
            $catatanList = KorlapRingkasan::catatanTerbaru(5, $id);

            $statKorlap = [
                'proyek_aktif_count' => $semuaJadwalAktif->count(),
                'total_panggilan' => $totalPanggilan,
                'total_hadir' => $totalHadir,
                'total_menunggu_val' => $totalMenungguVal,
                'total_tidak_hadir' => $totalTidakHadir,
                'total_belum_absen' => $totalBelumAbsen,
                'tingkat_kehadiran' => $tingkatKehadiran,
                'menunggu_count' => $menungguValidasiList->count(),
                'catatan_count' => $catatanList->count(),
                'catatan_sanksi' => $catatanList->where('jenis', 'sanksi')->count(),
                'catatan_info' => $catatanList->where('jenis', '!=', 'sanksi')->count(),
            ];

            $korlap = [
                'punyaTugas' => $punyaTugas,
                'hari' => $hari,
                'terdekat' => $hari['Hari ini']->isEmpty() ? KorlapRingkasan::shootingTerdekat($id) : null,
                'menunggu' => $menungguValidasiList,
                'catatan' => $catatanList,
                'statKorlap' => $statKorlap,
            ];
        }

        return view('admin.dashboard', $korlap + compact(
            'proyekAktif',
            'totalPendaftar',
            'perluDinego',
            'chartStatusPembayaran',
            'urgentProjects',
            'agendaEvents',
            'dari',
            'sampai',
            'skala',
            'pembayaranSengketa',
            'linkSengketa',
            'ringkasan',
            'tahapan',
            'breakdownAdmin',
            'proyekBottleneck'
        ));
    }

    /**
     * CO.2.3: tandai item tahapan yang punya pengingat SA belum dibaca ('diingatkan').
     * CU.7 fix: ikut kenal key kelompok 'pg-{admin}-{proyek}-{jenis}' (reminder lewat
     * kartu dashboard SA), bukan cuma 'pa-{app_id}' (reminder individual lama).
     */
    private function tandaiDiingatkan(array $tahapan, $user): array
    {
        $kunci = $user->unreadNotifications()->where('data->jenis', 'pengingat_sa')->get()
            ->map(fn ($n) => $n->data['kunci'] ?? null)->filter()->flip();

        return collect($tahapan)->map(function ($t) use ($kunci, $user) {
            $t['daftar'] = collect($t['daftar'])->map(function ($i) use ($kunci, $user) {
                $kunciGrup = 'pg-'.$user->id.'-'.$i['app']->casting_project_id.'-'.$i['jenis'];
                $i['diingatkan'] = $kunci->has('pa-'.$i['app']->id) || $kunci->has($kunciGrup);

                return $i;
            })->values();

            return $t;
        })->all();
    }
}
