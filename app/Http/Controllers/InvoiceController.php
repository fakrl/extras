<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
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

        $role = $request->user()->isClient() ? 'cd' : 'admin';
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

        ActivityLog::record(
            'SIGN_INVOICE',
            ucfirst($role)." {$request->user()->name} menandatangani invoice untuk proyek '{$castingProject->nama_produksi}'",
            $castingProject
        );

        return back()->with('status', 'Tanda tangan invoice berhasil disimpan.');
    }

    public function uploadCustomDoc(Request $request, CastingProject $castingProject): RedirectResponse
    {
        $this->pastikanBolehLihat($request, $castingProject);

        $request->validate([
            'custom_doc' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,docx,xlsx', 'max:10240'],
        ]);

        $path = $request->file('custom_doc')->store("invoices/custom/{$castingProject->id}", 'local');

        $invoice = $castingProject->invoices()->firstOrCreate([]);
        if ($invoice->custom_doc_path) {
            Storage::disk('local')->delete($invoice->custom_doc_path);
        }

        $invoice->update([
            'template_type' => 'custom_ph',
            'custom_doc_path' => $path,
        ]);

        ActivityLog::record(
            'UPLOAD_CUSTOM_INVOICE_DOC',
            "{$request->user()->name} mengunggah template voucher/invoice khusus untuk proyek '{$castingProject->nama_produksi}'",
            $castingProject
        );

        return back()->with('status', 'Dokumen invoice/voucher template khusus Client PH berhasil diupload.');
    }

    public function downloadCustomDoc(Request $request, CastingProject $castingProject)
    {
        $this->pastikanBolehLihat($request, $castingProject);

        $invoice = $castingProject->invoices()->first();
        abort_unless($invoice && $invoice->custom_doc_path && Storage::disk('local')->exists($invoice->custom_doc_path), 404);

        return Storage::disk('local')->download($invoice->custom_doc_path);
    }

    public function downloadPdf(Request $request, CastingProject $castingProject)
    {
        $this->pastikanBolehLihat($request, $castingProject);

        $invoice = $castingProject->invoices()->first();
        abort_unless($invoice && $invoice->pdf_path && Storage::disk('local')->exists($invoice->pdf_path), 404, 'Invoice PDF belum tersedia.');

        return Storage::disk('local')->download($invoice->pdf_path, 'Invoice-JBTB-'.Str::slug($castingProject->nama_produksi).'.pdf');
    }

    private function pastikanBolehLihat(Request $request, CastingProject $castingProject): void
    {
        $user = $request->user();

        abort_unless($user->isAdmin() || $user->isClient(), 403);

        if ($user->isClient()) {
            $isAssigned = $castingProject->cdAssignments()->where('cd_user_id', $user->id)->exists();
            $isOwner = $castingProject->diajukan_oleh_client_id === $user->id;
            abort_unless($isAssigned || $isOwner, 403);
        }
    }
}
