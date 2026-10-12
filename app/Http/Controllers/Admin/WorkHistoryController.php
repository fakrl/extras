<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaffPayroll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * RF-43/RF-44: Riwayat Kerja & Status Gaji berlaku uniform untuk SEMUA
 * tipe Admin (Default, Talco, Korlap, Sosmed), karena semua tetap dibayar
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

    public function slip(Request $request, StaffPayroll $staffPayroll)
    {
        abort_unless($request->user()->isSuperAdmin() || $staffPayroll->assignment?->user_id === $request->user()->id, 403);
        abort_unless($staffPayroll->pdf_slip_path && Storage::disk('local')->exists($staffPayroll->pdf_slip_path), 404);

        return Storage::disk('local')->download($staffPayroll->pdf_slip_path, "slip-honor-{$staffPayroll->id}.pdf");
    }
}
