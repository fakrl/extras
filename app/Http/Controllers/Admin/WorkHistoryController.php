<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * RF-43/RF-44: Riwayat Kerja & Status Gaji berlaku uniform untuk SEMUA
 * tipe Admin (Default, Talco, Korlap, Sosmed) — karena semua tetap dibayar
 * oleh Jestika (Super Admin) dan riwayat proyeknya menjadi basis honor
 * masing-masing. Untuk Talco/Sosmed, ini SATU-SATUNYA halaman yang mereka
 * akses (zero functional footprint di modul lain, sesuai Batasan Sistem).
 */
class WorkHistoryController extends Controller
{
    public function index(Request $request)
    {
        $assignments = $request->user()->adminProjectAssignments()
            ->with('castingProject', 'payroll.addons')
            ->latest()
            ->get();

        return view('admin.work-history', compact('assignments'));
    }
}
