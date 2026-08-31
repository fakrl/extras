<?php

namespace App\Http\Controllers\Cd;

use App\Http\Controllers\Controller;
use App\Models\CdReview;
use App\Models\ProjectApplication;
use Illuminate\Support\Facades\Auth;

/**
 * CD tidak pernah lihat data fee/margin/nama asli Extras (tembok visibilitas,
 * CLAUDE.md §5) — chart di sini murni soal keputusan review, bukan finansial.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $cdId = Auth::id();

        $perluDireview = ProjectApplication::where('status_partisipasi', 'diajukan_ke_cd')
            ->whereHas('castingProject.cdAssignments', fn ($q) => $q->where('cd_user_id', $cdId))
            ->count();

        $approve = CdReview::where('cd_id', $cdId)->where('keputusan', 'approve')->count();
        $reject = CdReview::where('cd_id', $cdId)->where('keputusan', 'reject')->count();

        $chartKeputusan = [
            'labels' => ['Approve', 'Reject'],
            'data' => [$approve, $reject],
        ];

        return view('cd.dashboard', compact('perluDireview', 'chartKeputusan'));
    }
}
