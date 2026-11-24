<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** DA.2: halaman persetujuan Kebijakan Privasi & S&K untuk Extras yang belum pernah setuju (lihat CekPersetujuanPrivasi). */
class PrivasiConsentController extends Controller
{
    public function show(Request $request)
    {
        if ($request->user()->privasi_disetujui_at !== null) {
            return redirect($request->user()->dashboardUrl());
        }

        return view('auth.privasi-consent');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'setuju_privasi' => ['accepted'],
        ], [
            'setuju_privasi.accepted' => 'Centang persetujuan Kebijakan Privasi dan Syarat & Ketentuan dulu.',
        ]);

        $request->user()->forceFill(['privasi_disetujui_at' => now()])->save();

        return redirect($request->user()->dashboardUrl());
    }
}
