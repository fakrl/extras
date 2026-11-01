<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\CastingProject;
use App\Models\ClientReview;
use App\Models\EventShootingDate;
use App\Models\Invoice;
use App\Models\ProjectApplication;
use App\Support\Timeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $klienId = Auth::id();

        $proyekIds = CastingProject::query()->milikClient(Auth::user())->where('client_request_status', 'disetujui')->pluck('id');

        $perluDireview = ProjectApplication::where('status_partisipasi', 'diajukan_ke_client')
            ->whereIn('casting_project_id', $proyekIds)
            ->count();

        $approve = ClientReview::where('client_id', $klienId)->where('keputusan', 'approve')->count();
        $reject = ClientReview::where('client_id', $klienId)->where('keputusan', 'reject')->count();

        // CL-11: berjalan = lowongan masih dibuka atau shooting sedang berlangsung.
        $proyekBerjalan = CastingProject::whereIn('id', $proyekIds)
            ->where(fn ($q) => $q->where('status', 'dibuka')->orWhere(fn ($t) => $t->diTahap('berjalan')))
            ->with(['classes', 'shootingDates', 'applications.clientReviews'])
            ->get()
            ->each(function ($p) use ($klienId) {
                $target = (int) $p->classes->sum('kuota_kelas');
                $terpilih = (int) $p->applications->filter(fn ($a) => $a->clientReviews->where('client_id', $klienId)->where('keputusan', 'approve')->isNotEmpty())->count();
                $menunggu = (int) $p->applications->where('status_partisipasi', 'diajukan_ke_client')->count();
                $p->kuota_target = $target;
                $p->kuota_terpilih = $terpilih;
                $p->kandidat_menunggu = $menunggu;
                $p->persen_kuota = $target > 0 ? (int) round(($terpilih / $target) * 100) : 0;
            });

        // Hitung Ringkasan Tagihan Client
        $invoicesClient = Invoice::whereIn('casting_project_id', $proyekIds)->with('castingProject.classes')->get();
        $invLunasCount = 0;
        $invLunasNominal = 0.0;
        $invBelumLunasCount = 0;
        $invBelumLunasNominal = 0.0;

        foreach ($invoicesClient as $inv) {
            $val = (float) ($inv->nominal ?? 0);
            if ($inv->status_bayar === 'lunas') {
                $invLunasCount++;
                $invLunasNominal += $val;
            } else {
                $invBelumLunasCount++;
                $invBelumLunasNominal += $val;
            }
        }
        $proyekTanpaInvoice = max(0, $proyekIds->count() - $invoicesClient->count());

        $statBillingClient = [
            'total_invoice_count' => $invoicesClient->count(),
            'lunas_count' => $invLunasCount,
            'lunas_nominal' => $invLunasNominal,
            'belum_lunas_count' => $invBelumLunasCount,
            'belum_lunas_nominal' => $invBelumLunasNominal,
            'belum_terbit_count' => $proyekTanpaInvoice,
        ];

        // CL-10: tugas Client sendiri, bukan tugas tim JBTB.
        $invoiceMenungguTtd = Invoice::whereIn('casting_project_id', $proyekIds)
            ->whereNotNull('ttd_admin_signature_path')->whereNull('ttd_client_signature_path')
            ->with('castingProject:id,nama_produksi')->get();
        $jadwalKosong = EventShootingDate::whereIn('casting_project_id', $proyekIds)
            ->whereDate('tanggal', '>=', today())->whereNull('lokasi')
            ->with('castingProject:id,nama_produksi')->get()->groupBy('casting_project_id');

        $pengajuan = CastingProject::query()->milikClient(Auth::user())->whereNotNull('brief_catatan')
            ->latest()
            ->take(5)
            ->get(['id', 'nama_produksi', 'client_request_status', 'alasan_tolak', 'created_at']);

        $karakterPendaftar = ProjectApplication::whereIn('casting_project_id', $proyekIds)
            ->terlihatClient()
            ->with('castingProjectClass:id,nama_kelas', 'castingProject:id,nama_produksi')
            ->get()
            ->groupBy(fn ($app) => $app->casting_project_id.'|'.($app->castingProjectClass->id ?? 0))
            ->map(fn ($group) => [
                'proyek' => $group->first()->castingProject->nama_produksi,
                'karakter' => $group->first()->castingProjectClass->nama_kelas ?? '-',
                'jumlah' => $group->count(),
            ])
            ->values();

        [$dari, $sampai, $skala] = Timeline::dariSesi($request, 'timeline_client_dashboard', 'minggu');
        $agendaEvents = EventShootingDate::whereIn('casting_project_id', $proyekIds)
            ->whereBetween('tanggal', [$dari, $sampai])
            ->with('castingProject:id,nama_produksi')
            ->get()
            ->each(fn ($e) => $e->nama_produksi = $e->castingProject?->nama_produksi);

        return view('client.dashboard', compact(
            'perluDireview', 'approve', 'reject', 'proyekBerjalan', 'pengajuan', 'karakterPendaftar',
            'agendaEvents', 'dari', 'sampai', 'skala', 'invoiceMenungguTtd', 'jadwalKosong',
            'statBillingClient'
        ));
    }
}
