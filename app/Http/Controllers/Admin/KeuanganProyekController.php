<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\ProjectExpense;
use App\Models\StaffPayroll;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class KeuanganProyekController extends Controller
{
    /**
     * BD.2.5: rekap-margin lama digabung ke Proyek & Keuangan; tab lama jadi filter.
     */
    public function rekapMargin(Request $request): RedirectResponse
    {
        $bayar = in_array($request->query('tab'), ['staf', 'extras'], true) ? $request->query('tab') : null;

        return redirect()->route('admin.projects.index', array_filter(['bayar' => $bayar]));
    }

    public function tandaiLunas(Request $request, CastingProject $castingProject): RedirectResponse
    {
        $data = $request->validate(['nominal' => ['required', 'numeric', 'min:0']]);

        $invoice = $castingProject->invoices()->firstOrCreate([]);
        if ($invoice->isLunas()) {
            return back()->with('error', 'Invoice ini sudah ditandai lunas.');
        }

        $invoice->update(['nominal' => $data['nominal'], 'status_bayar' => 'lunas', 'dibayar_at' => now()]);

        ActivityLog::record(
            'INVOICE_PAID',
            "Invoice proyek '{$castingProject->nama_produksi}' ditandai lunas (Rp ".number_format($data['nominal'], 0, ',', '.').')',
            $invoice
        );

        return back()->with('status', 'Invoice ditandai lunas.');
    }

    public function storeExpense(Request $request, CastingProject $castingProject): RedirectResponse
    {
        if ($castingProject->client_request_status !== 'disetujui') {
            return back()->with('error', 'Biaya hanya bisa dicatat untuk proyek yang sudah disetujui.');
        }

        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'nominal' => ['required', 'numeric', 'min:1'],
            'tanggal' => ['required', 'date'],
        ]);

        $expense = $castingProject->expenses()->create($data + ['created_by' => $request->user()->id]);

        ActivityLog::record(
            'PROJECT_EXPENSE_ADDED',
            "Biaya '{$expense->label}' Rp ".number_format($expense->nominal, 0, ',', '.')." ditambahkan ke proyek '{$castingProject->nama_produksi}'",
            $expense
        );

        return back()->with('status', 'Biaya lain-lain ditambahkan.');
    }

    public function destroyExpense(Request $request, ProjectExpense $projectExpense): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isSuperAdmin() || (int) $projectExpense->created_by === $user->id, 403, 'Hanya pembuat atau Super Admin yang bisa menghapus biaya ini.');

        $projectExpense->delete();

        ActivityLog::record(
            'PROJECT_EXPENSE_DELETED',
            "Biaya '{$projectExpense->label}' Rp ".number_format($projectExpense->nominal, 0, ',', '.')." dihapus dari proyek '{$projectExpense->castingProject?->nama_produksi}'",
            $projectExpense
        );

        return back()->with('status', 'Biaya lain-lain dihapus.');
    }

    /**
     * SPEC AV.3: tandai honor staf sudah dibayarkan.
     */
    public function tandaiDibayar(StaffPayroll $staffPayroll): RedirectResponse
    {
        if ($staffPayroll->isDibayar()) {
            return back()->with('error', 'Honor staf ini sudah ditandai dibayar.');
        }

        $staffPayroll->tandaiDibayar();

        ActivityLog::record(
            'STAFF_PAYROLL_PAID',
            "Honor staf {$staffPayroll->assignment?->user?->name} untuk proyek '{$staffPayroll->assignment?->castingProject?->nama_produksi}' ditandai sudah dibayar",
            $staffPayroll
        );

        return back()->with('status', 'Honor staf berhasil ditandai sudah dibayar.');
    }
}
