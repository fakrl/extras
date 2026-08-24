<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;

/**
 * RF-50 (diperluas): dashboard monitoring Super Admin mencakup ringkasan
 * SEMUA akun sistem — Extras, Casting Director, dan Admin (Default+sub-role)
 * — bukan cuma staf internal. Ini SENGAJA read-only: Super Admin bisa lihat
 * siapa aktif/nonaktif, tapi TIDAK ada aksi ubah status di sini. Aksi
 * nonaktifkan Extras/CD tetap hak Admin Default (RF-05, tidak berubah).
 */
class MonitoringController extends Controller
{
    public function index()
    {
        $extrasAktif = User::where('role', 'extras')->where('status', 'aktif')->count();
        $extrasTotal = User::where('role', 'extras')->count();
        $cdTotal = User::where('role', 'casting_director')->count();
        $adminTotal = User::whereIn('role', ['admin_default', 'admin_talco', 'admin_korlap', 'admin_sosmed'])->count();

        $extrasList = User::where('role', 'extras')->with('extrasProfile')->latest()->get();
        $cdList = User::where('role', 'casting_director')->latest()->get();

        return view('super-admin.monitoring', compact(
            'extrasAktif', 'extrasTotal', 'cdTotal', 'adminTotal', 'extrasList', 'cdList'
        ));
    }
}
