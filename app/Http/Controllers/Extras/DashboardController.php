<?php

namespace App\Http\Controllers\Extras;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $extrasProfile = Auth::user()->extrasProfile;

        // Semua pendaftaran ditampilkan (bukan cuma yang aktif), konsisten
        // dengan tampilan lama, biar histori tidak hilang dari sudut pandang Extras.
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

        return view('extras.dashboard', compact('extrasProfile', 'pendaftaranSaya', 'aktivitasSaya'));
    }
}
