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

        $reimbursements = $assignments->flatMap(function ($assignment) {
            return $assignment->payroll?->addons->map(function ($addon) use ($assignment) {
                return (object) [
                    'id' => $addon->id,
                    'proyek' => $assignment->castingProject->nama_produksi,
                    'label' => $addon->label,
                    'nominal' => $addon->nominal,
                    'tanggal' => $addon->created_at,
                    'slip_status' => $assignment->payroll?->pdf_slip_path ? 'Tercatat di Slip' : 'Menunggu Slip',
                ];
            }) ?? collect();
        })->sortByDesc('tanggal')->values();

        return view('admin.work-history', compact('assignments', 'reimbursements'));
    }
}
