<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AdminProjectAssignment;
use App\Models\CastingProject;
use App\Models\StaffPayroll;
use App\Models\User;
use App\Services\PdfGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectAssignmentController extends Controller
{
    public function __construct(private PdfGeneratorService $pdfGenerator) {}

    /**
     * RF-42: Super Admin menugaskan sub-admin ke proyek casting tertentu
     * sesuai kebutuhan proyek tersebut — tidak wajib tiap proyek.
     */
    public function assign(Request $request, CastingProject $castingProject): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $user = User::findOrFail($data['user_id']);
        abort_unless($user->isAnyAdmin(), 422, 'Hanya bisa menugaskan akun bertipe Admin.');

        $castingProject->adminAssignments()->firstOrCreate([
            'user_id' => $user->id,
        ], [
            'assigned_by' => $request->user()->id,
            'status_log' => 'berjalan',
        ]);

        return back()->with('status', "{$user->name} berhasil ditugaskan ke proyek ini.");
    }

    /**
     * RF-45/RF-46/RF-48: proyek dinyatakan selesai -> log jadi "selesai" ->
     * dasar kelayakan honor -> auto-generate slip honor PDF.
     */
    public function markComplete(AdminProjectAssignment $assignment): RedirectResponse
    {
        abort_if($assignment->status_log === 'selesai', 422, 'Penugasan ini sudah ditandai selesai sebelumnya.');

        $payroll = $assignment->tandaiSelesai();

        $assignment->load('user', 'castingProject');
        $path = "payrolls/pdf/{$payroll->id}.pdf";
        $this->pdfGenerator->generate('payrolls.pdf-template', compact('assignment', 'payroll'), $path);
        $payroll->tandaiSlipDibuat($path);

        return back()->with('status', 'Penugasan ditandai selesai, slip honor telah dibuat.');
    }

    /**
     * RF-47: add-on/reimburse manual pada catatan honor staf.
     */
    public function addAddon(Request $request, StaffPayroll $payroll): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'nominal' => ['required', 'numeric', 'min:0'],
        ]);

        $payroll->addons()->create([
            'label' => $data['label'],
            'nominal' => $data['nominal'],
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Komponen tambahan berhasil ditambahkan.');
    }
}
