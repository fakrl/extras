<?php

namespace App\Http\Controllers\Extras;

use App\Http\Controllers\Controller;
use App\Models\ProjectApplication;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $extrasProfile = Auth::user()->extrasProfile;

        // Semua pendaftaran ditampilkan (bukan cuma yang aktif) — konsisten
        // dengan tampilan lama, biar histori tidak hilang dari sudut pandang Extras.
        $pendaftaranSaya = $extrasProfile
            ? ProjectApplication::where('extras_id', $extrasProfile->id)
                ->with('castingProject')
                ->latest()
                ->get()
            : collect();

        return view('extras.dashboard', compact('extrasProfile', 'pendaftaranSaya'));
    }
}
