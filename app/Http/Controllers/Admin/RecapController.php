<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ExtrasRecapExport;
use App\Http\Controllers\Controller;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use Maatwebsite\Excel\Facades\Excel;

class RecapController extends Controller
{
    /**
     * RF-51: rekap Extras yang paling sering terpilih (status lolos ke atas)
     * dan rekap status keaktifan Extras.
     */
    public function index()
    {
        $extrasPalingSering = ExtrasProfile::withCount([
            'applications' => fn ($q) => $q->whereIn('status_partisipasi', ProjectApplication::STATUS_LOLOS_KE_ATAS),
        ])
            ->orderByDesc('applications_count')
            ->take(10)
            ->get();

        $rekapStatus = ExtrasProfile::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.recap.index', compact('extrasPalingSering', 'rekapStatus'));
    }

    /**
     * RF-52: ekspor rekap ke format Excel (.xlsx).
     */
    public function export()
    {
        return Excel::download(new ExtrasRecapExport, 'rekap-extras-'.now()->format('Y-m-d').'.xlsx');
    }
}
