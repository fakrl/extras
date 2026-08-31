<?php

namespace App\Http\Controllers;

use App\Models\CastingProject;
use App\Services\PdfGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * RF-31: invoice ke client, ditandatangani Admin Default & CD. Diakses
 * lintas role sama seperti ContractController — otorisasi granular di
 * dalam method, bukan lewat role middleware group.
 */
class InvoiceController extends Controller
{
    public function __construct(private PdfGeneratorService $pdfGenerator) {}

    public function show(Request $request, CastingProject $castingProject)
    {
        $this->pastikanBolehLihat($request, $castingProject);

        $invoice = $castingProject->invoices()->firstOrCreate([]);

        $castingProject->load('classes', 'applications.extras');

        return view('invoices.show', compact('castingProject', 'invoice'));
    }

    public function sign(Request $request, CastingProject $castingProject): RedirectResponse
    {
        $this->pastikanBolehLihat($request, $castingProject);

        $data = $request->validate(['signature' => ['required', 'string']]);

        $role = $request->user()->role === 'casting_director' ? 'cd' : 'admin';
        $filename = "invoices/signatures/{$castingProject->id}-{$role}-".Str::random(8).'.png';

        $base64 = preg_replace('#^data:image/\w+;base64,#', '', $data['signature']);
        Storage::disk('local')->put($filename, base64_decode($base64));

        $invoice = $castingProject->invoices()->firstOrCreate([]);
        $invoice->update([
            $role === 'cd' ? 'ttd_cd_signature_path' : 'ttd_admin_signature_path' => $filename,
        ]);

        if ($invoice->ttd_admin_signature_path && $invoice->ttd_cd_signature_path) {
            $castingProject->load('classes', 'applications.extras');
            $path = "invoices/pdf/{$castingProject->id}.pdf";
            $this->pdfGenerator->generate('invoices.pdf-template', compact('castingProject', 'invoice'), $path);
            $invoice->update(['pdf_path' => $path]);
        }

        return back()->with('status', 'Tanda tangan invoice berhasil disimpan.');
    }

    private function pastikanBolehLihat(Request $request, CastingProject $castingProject): void
    {
        $user = $request->user();

        abort_unless(in_array($user->role, ['admin_default', 'casting_director'], true), 403);

        if ($user->role === 'casting_director') {
            abort_unless($castingProject->cdAssignments()->where('cd_user_id', $user->id)->exists(), 403);
        }
    }
}
