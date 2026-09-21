<?php

namespace App\Http\Controllers\Extras;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\EventShootingDate;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $extrasProfile = $user->extrasProfile;

        $pendaftaranSaya = $extrasProfile
            ? ProjectApplication::where('extras_id', $extrasProfile->id)
                ->with('castingProject.shootingDates')
                ->latest()
                ->get()
            : collect();

        $aktivitasSaya = collect();
        if ($extrasProfile) {
            $aktivitasSaya = ActivityLog::where(function ($q) use ($extrasProfile) {
                $q->where('user_id', Auth::id())
                    ->orWhere(function ($sq) use ($extrasProfile) {
                        $sq->where('subject_type', ExtrasProfile::class)
                            ->where('subject_id', $extrasProfile->id);
                    });
            })->latest('created_at')->take(5)->get();
        }

        $proyekLolos = $extrasProfile
            ? ProjectApplication::where('extras_id', $extrasProfile->id)
                ->whereIn('status_partisipasi', ProjectApplication::STATUS_LOLOS_KE_ATAS)
                ->pluck('casting_project_id')
            : collect();

        $jadwalBulanIni = EventShootingDate::whereIn('casting_project_id', $proyekLolos)
            ->whereBetween('tanggal', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->with('castingProject:id,nama_produksi')
            ->get()
            ->map(fn ($e) => tap($e, fn ($e) => $e->nama_produksi = $e->castingProject?->nama_produksi));

        return view('extras.dashboard', compact('extrasProfile', 'pendaftaranSaya', 'aktivitasSaya', 'jadwalBulanIni'));
    }
}
