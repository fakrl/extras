<?php

namespace App\Services;

use App\Models\CastingProject;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ProjectApplication;
use App\Models\ProjectExpense;
use App\Models\StaffPayroll;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class KeuanganService
{
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
     * BD.2: nilai tagihan invoice = rumus PDF (budget_client x kuota_kelas).
     * Kalau D5 mengubah rumus, cukup ubah di sini.
     */
    public function nilaiInvoice(CastingProject $project): float
    {
        return (float) $this->rincianInvoice($project)->total;
    }

    /**
     * BD.2: relasi yang dibutuhkan cashflowProyek(), eager-load di daftar proyek biar tidak N+1.
     */
    public const RELASI_CASHFLOW = [
        'classes', 'invoices', 'expenses.pembuat',
        'payments.addons', 'payments.projectApplication.extras.user',
        'payrolls.addons', 'payrolls.assignment.user',
    ];

    /**
     * BD.2/BE.3: cashflow per proyek (basis tagihan/kewajiban, bukan tanggal transaksi).
     * Baris invoice: nominal tersimpan, sebelum itu nilaiInvoice() live (juga baris perkiraan kalau invoice belum dibuat).
     * Masuk = invoice lunas, Piutang = invoice (sudah dibuat) belum lunas, Keluar = honor Extras + honor staf + biaya lain-lain.
     * BG.2: keluar_dibayar = payment ditransfer + payroll sudah + biaya lain-lain (sama dengan ringkasanPeriode), keluar_belum = sisanya.
     * Saldo = masuk - keluar, Proyeksi = masuk + piutang - keluar, Terpakai % = keluar / total tagihan.
     */
    public function cashflowProyek(CastingProject $project): object
    {
        $project->loadMissing(self::RELASI_CASHFLOW);
        $nilai = $this->nilaiInvoice($project);

        $masuk = $project->invoices->isEmpty()
            ? collect([(object) ['invoice' => null, 'nominal' => $nilai, 'lunas' => false]])
            : $project->invoices->map(fn (Invoice $i) => (object) [
                'invoice' => $i,
                'nominal' => (float) ($i->nominal ?? $nilai),
                'lunas' => $i->isLunas(),
            ]);

        $extras = $project->payments->map(fn (Payment $p) => (object) [
            'payment' => $p,
            'nominal' => $p->nominalTotal(),
            'lunas' => $p->ditransfer_at !== null,
        ]);

        $staf = $project->payrolls->map(fn (StaffPayroll $s) => (object) [
            'payroll' => $s,
            'nominal' => $s->nominalTotal(),
            'lunas' => $s->isDibayar(),
        ]);

        $totalMasuk = (float) $masuk->where('lunas', true)->sum('nominal');
        $piutang = (float) $masuk->where('lunas', false)->whereNotNull('invoice')->sum('nominal');
        $totalKeluar = $extras->sum('nominal') + $staf->sum('nominal') + (float) $project->expenses->sum('nominal');
        $keluarBelum = (float) $extras->where('lunas', false)->sum('nominal') + (float) $staf->where('lunas', false)->sum('nominal');
        $tagihan = (float) $masuk->sum('nominal');

        return (object) [
            'masuk' => $masuk,
            'extras' => $extras,
            'staf' => $staf,
            'biaya' => $project->expenses,
            'total_masuk' => $totalMasuk,
            'piutang' => $piutang,
            'total_keluar' => $totalKeluar,
            'keluar_dibayar' => $totalKeluar - $keluarBelum,
            'keluar_belum' => $keluarBelum,
            'saldo' => $totalMasuk - $totalKeluar,
            'proyeksi' => $totalMasuk + $piutang - $totalKeluar,
            'persen_terpakai' => $tagihan > 0 ? round($totalKeluar / $tagihan * 100, 1) : null,
        ];
    }

    /**
     * BD.2/BD.3: uang per periode berdasar tanggal transaksi (invoices.dibayar_at,
     * payments.ditransfer_at, staff_payrolls.dibayar_at, project_expenses.tanggal).
     * BE.3: piutang belum punya tanggal transaksi, jadi = invoice belum lunas dari proyek yang
     * shooting-nya dalam periode (filter proyek dashboard), nilai sama dengan cashflowProyek().
     */
    public function ringkasanPeriode(Carbon $from, Carbon $to): object
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        $masuk = Invoice::where('status_bayar', 'lunas')->whereBetween('dibayar_at', [$from, $to])
            ->with('castingProject.classes')->get()
            ->map(fn (Invoice $i) => [$i->dibayar_at, (float) ($i->nominal ?? $this->nilaiInvoice($i->castingProject))]);

        $keluar = Payment::whereBetween('ditransfer_at', [$from, $to])->with('projectApplication', 'addons')->get()->toBase()
            ->map(fn (Payment $p) => [$p->ditransfer_at, $p->nominalTotal()])
            ->merge(StaffPayroll::where('status_bayar', 'sudah')->whereBetween('dibayar_at', [$from, $to])->with('addons')->get()
                ->map(fn (StaffPayroll $s) => [$s->dibayar_at, $s->nominalTotal()]))
            ->merge(ProjectExpense::whereDate('tanggal', '>=', $from)->whereDate('tanggal', '<=', $to)->get()
                ->map(fn (ProjectExpense $e) => [$e->tanggal, (float) $e->nominal]));

        $perBulan = fn (Collection $rows) => $rows->groupBy(fn ($r) => $r[0]->format('Y-m'))->map(fn ($g) => $g->sum(1));
        $masukBulan = $perBulan($masuk);
        $keluarBulan = $perBulan($keluar);

        $bulan = collect(CarbonPeriod::create($from->copy()->startOfMonth(), '1 month', $to))
            ->map(function ($m) use ($masukBulan, $keluarBulan) {
                $k = $m->format('Y-m');

                return (object) [
                    'bulan' => $k,
                    'label' => $m->translatedFormat('M Y'),
                    'masuk' => (float) ($masukBulan[$k] ?? 0),
                    'keluar' => (float) ($keluarBulan[$k] ?? 0),
                    'saldo' => (float) (($masukBulan[$k] ?? 0) - ($keluarBulan[$k] ?? 0)),
                ];
            })->values();

        $totalMasuk = (float) $masuk->sum(1);
        $totalKeluar = (float) $keluar->sum(1);
        $piutang = (float) Invoice::where('status_bayar', '!=', 'lunas')
            ->whereHas('castingProject', fn ($q) => $q->shootingDalam($from, $to))
            ->with('castingProject.classes')->get()
            ->sum(fn (Invoice $i) => (float) ($i->nominal ?? $this->nilaiInvoice($i->castingProject)));

        return (object) [
            'total_masuk' => $totalMasuk,
            'piutang' => $piutang,
            'total_keluar' => $totalKeluar,
            'saldo' => $totalMasuk - $totalKeluar,
            'proyeksi' => $totalMasuk + $piutang - $totalKeluar,
            'per_bulan' => $bulan,
        ];
    }
}
