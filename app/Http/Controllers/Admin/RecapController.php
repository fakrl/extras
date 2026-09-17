<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ExtrasRecapExport;
use App\Http\Controllers\Controller;
use App\Models\ExtrasCategory;
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
        $kategoriId = request()->query('kategori_id');

        $extrasPalingSering = ExtrasProfile::withCount([
            'applications' => fn ($q) => $q->whereIn('status_partisipasi', ProjectApplication::STATUS_LOLOS_KE_ATAS),
        ])
            ->with('user:id,username', 'categories')
            ->when($kategoriId, fn ($q) => $q->whereHas('categories', fn ($q2) => $q2->where('extras_categories.id', $kategoriId)))
            ->orderByDesc('applications_count')
            ->take(10)
            ->get();

        $rekapStatus = ExtrasProfile::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // RF-07/08: rekap Extras paling sering membatalkan mendadak — pelengkap
        // "Kelola Extras" (docs/CLAUDE.md §9), sebelumnya cuma ada agregat status,
        // belum ada ranking per-individu.
        $extrasSeringBatal = ExtrasProfile::where('cancel_count', '>', 0)
            ->with('user:id,username')
            ->orderByDesc('cancel_count')
            ->take(10)
            ->get();

        $allCategories = ExtrasCategory::orderBy('nama')->get();

        return view('admin.recap.index', compact('extrasPalingSering', 'rekapStatus', 'extrasSeringBatal', 'allCategories'));
    }

    /**
     * RF-52: ekspor rekap ke format Excel (.xlsx).
     */
    public function export()
    {
        return Excel::download(new ExtrasRecapExport, 'rekap-extras-'.now()->format('Y-m-d').'.xlsx');
    }
}
