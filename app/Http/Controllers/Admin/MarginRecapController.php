<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\StaffPayroll;
use App\Services\KeuanganService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MarginRecapController extends Controller
{
    public function __construct(
        protected KeuanganService $keuanganService
    ) {}

    /**
     * SPEC AV.2 & RF-30: Halaman Penggajian & Keuangan (Keuangan)
     * Mencakup 4 pilar: Margin Proyek, Honor Staf, Honor Extras, dan Invoice Client.
     */
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'margin');

        $projects = $this->keuanganService->ringkasanMarginSemuaProyek();
        $staffPayrolls = $this->keuanganService->daftarHonorStaf();
        $extrasPayments = $this->keuanganService->daftarHonorExtras();
        $clientInvoices = $this->keuanganService->daftarInvoiceClient();
        $marginBulanIni = $this->keuanganService->marginBulanIni();

        return view('admin.recap.margin', compact(
            'tab',
            'projects',
            'staffPayrolls',
            'extrasPayments',
            'clientInvoices',
            'marginBulanIni'
        ));
    }

    /**
     * SPEC AV.3: Tandai honor staf sudah dibayarkan.
     */
    public function tandaiDibayar(StaffPayroll $staffPayroll): RedirectResponse
    {
        $staffPayroll->tandaiDibayar();

        ActivityLog::record(
            'STAFF_PAYROLL_PAID',
            "Honor staf {$staffPayroll->assignment?->user?->name} untuk proyek '{$staffPayroll->assignment?->castingProject?->nama_produksi}' ditandai sudah dibayar",
            $staffPayroll
        );

        return back()->with('status', 'Honor staf berhasil ditandai sudah dibayar.');
    }
}
