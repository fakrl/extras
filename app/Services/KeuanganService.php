<?php

namespace App\Services;

use App\Models\CastingProject;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ProjectApplication;
use App\Models\StaffPayroll;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class KeuanganService
{
    /**
     * Hitung detail margin untuk satu proyek.
     */
    public function hitungMarginProyek(CastingProject $project): object
    {
        $breakdown = collect();
        $belumTerklasifikasi = null;
        $totalFeeClient = 0.0;
        $totalPayout = 0.0;

        $tanpaKelas = $project->applications->whereNull('casting_project_class_id');

        if ($tanpaKelas->isNotEmpty()) {
            $payout = (float) $tanpaKelas->sum('fee_final');
            $totalPayout += $payout;

            $belumTerklasifikasi = (object) [
                'jumlah_aplikasi' => $tanpaKelas->count(),
                'total_payout' => $payout,
            ];
        }

        foreach ($project->applications->whereNotNull('casting_project_class_id')->groupBy('casting_project_class_id') as $aplikasi) {
            $payout = (float) $aplikasi->sum('fee_final');
            $totalPayout += $payout;

            $feeClient = (float) ($aplikasi->first()->castingProjectClass->budget_client ?? 0) * $aplikasi->count();
            $totalFeeClient += $feeClient;

            $nullCount = $aplikasi->whereNull('fee_final')->count();

            $breakdown->push((object) [
                'kelas' => $aplikasi->first()->castingProjectClass,
                'jumlah_aplikasi' => $aplikasi->count(),
                'total_fee_client' => $feeClient,
                'total_payout' => $payout,
                'margin' => $feeClient - $payout,
                'ada_fee_null' => $nullCount > 0,
                'jumlah_fee_null' => $nullCount,
                'aplikasi_list' => $aplikasi,
            ]);
        }

        $margin = $totalFeeClient - $totalPayout;

        return (object) [
            'project' => $project,
            'breakdown' => $breakdown,
            'belum_terklasifikasi' => $belumTerklasifikasi,
            'total_fee_client' => $totalFeeClient,
            'total_payout' => $totalPayout,
            'margin' => $margin,
            'margin_persen' => $totalFeeClient > 0 ? ($margin / $totalFeeClient * 100) : 0,
        ];
    }

    /**
     * Dapatkan ringkasan margin untuk seluruh proyek.
     */
    public function ringkasanMarginSemuaProyek(): Collection
    {
        return CastingProject::with(['applications' => function ($q) {
            $q->whereIn('status_partisipasi', ProjectApplication::STATUS_LOLOS_KE_ATAS)
                ->with(['castingProjectClass', 'extras.user']);
        }])->get()->map(fn (CastingProject $project) => $this->hitungMarginProyek($project));
    }

    /**
     * Hitung total margin bulan ini.
     */
    public function marginBulanIni(): object
    {
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $apps = ProjectApplication::whereIn('status_partisipasi', ['selesai_produksi'])
            ->whereBetween('updated_at', [$start, $end])
            ->whereNotNull('casting_project_class_id')
            ->with('castingProjectClass:id,budget_client')
            ->get();

        $totalFeeClient = $apps->sum(fn ($a) => (float) ($a->castingProjectClass->budget_client ?? 0));
        $totalPayout = $apps->sum(fn ($a) => (float) ($a->fee_final ?? 0));
        $margin = $totalFeeClient - $totalPayout;

        return (object) [
            'margin' => $margin,
            'ada_data' => $apps->isNotEmpty(),
            'total_fee_client' => $totalFeeClient,
            'total_payout' => $totalPayout,
        ];
    }

    /**
     * Hitung trend margin bulanan (default 6 bulan terakhir).
     */
    public function trendMarginBulanan(int $months = 6): array
    {
        $labels = [];
        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $bulan = Carbon::now()->subMonths($i);
            $labels[] = $bulan->translatedFormat('M Y');
            $apps = ProjectApplication::whereIn('status_partisipasi', ['selesai_produksi'])
                ->whereBetween('updated_at', [$bulan->copy()->startOfMonth(), $bulan->copy()->endOfMonth()])
                ->whereNotNull('casting_project_class_id')
                ->with('castingProjectClass:id,budget_client')
                ->get();
            $fee = $apps->sum(fn ($a) => (float) ($a->castingProjectClass->budget_client ?? 0));
            $payout = $apps->sum(fn ($a) => (float) ($a->fee_final ?? 0));
            $data[] = round($fee - $payout);
        }

        return compact('labels', 'data');
    }

    /**
     * Dapatkan daftar honor staf (StaffPayroll).
     */
    public function daftarHonorStaf(): Collection
    {
        return StaffPayroll::with([
            'assignment.castingProject',
            'assignment.user',
            'addons',
        ])->latest()->get();
    }

    /**
     * Dapatkan daftar honor extras (Payment).
     */
    public function daftarHonorExtras(): Collection
    {
        return Payment::with([
            'projectApplication.extras.user',
            'projectApplication.castingProject',
            'addons',
        ])->latest()->get();
    }

    /**
     * SPEC AY.1.15: total honor staf yang belum dibayar (pokok + addon),
     * single source of truth dipakai dashboard Super Admin & tab Honor Staf.
     */
    public function totalHonorStafBelumDiproses(): float
    {
        return StaffPayroll::where('status_bayar', 'belum')
            ->with('addons')
            ->get()
            ->sum(fn (StaffPayroll $p) => $p->nominalTotal());
    }

    /**
     * Rincian invoice per kelas (peran x jumlah x fee = subtotal), dipakai
     * sama-sama oleh PDF invoice dan halaman invoices/show Client. Basis
     * kuota_kelas x budget_client, BUKAN jumlah aplikasi aktual (D5 belum
     * diputus, rumus ini yang dipakai PDF invoice sekarang).
     */
    public function rincianInvoice(CastingProject $project): object
    {
        $rows = $project->classes->map(fn ($class) => (object) [
            'nama_kelas' => $class->nama_kelas,
            'kuota_kelas' => $class->kuota_kelas,
            'budget_client' => (float) $class->budget_client,
            'subtotal' => (float) $class->budget_client * $class->kuota_kelas,
        ]);

        return (object) [
            'rows' => $rows,
            'total' => $rows->sum('subtotal'),
        ];
    }

    /**
     * Dapatkan daftar invoice client.
     */
    public function daftarInvoiceClient(): Collection
    {
        return Invoice::with([
            'castingProject',
        ])->latest()->get();
    }
}
