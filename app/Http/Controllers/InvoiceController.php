<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Services\KeuanganService;
use App\Services\PdfGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * RF-31: invoice ke client, ditandatangani Admin Default & Client. Diakses
 * lintas role sama seperti ContractController, otorisasi granular di
 * dalam method, bukan lewat role middleware group.
 */
class InvoiceController extends Controller
{
    public function __construct(
        private PdfGeneratorService $pdfGenerator,
        private KeuanganService $keuangan,
    ) {}

    public function indexClient(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isClient(), 403);

        $projects = CastingProject::query()->milikClient($user)->where('client_request_status', 'disetujui')->with('invoices', 'classes')->orderByDesc('id')->get();

        // CL-06: status & nominal tagihan, bukan status rekrutmen.
        $tagihan = $projects->mapWithKeys(function ($p) {
            $inv = $p->invoices->first();
            [$label, $badge] = match (true) {
                ! $inv => ['Belum ada invoice', 'badge-netral'],
                $inv->isLunas() => ['Lunas', 'badge-aktif'],
                ! $inv->ttd_client_signature_path => ['Belum TTD', 'badge-pending'],
                default => ['Menunggu bayar', 'badge-info'],
            };

            return [$p->id => ['label' => $label, 'badge' => $badge, 'nominal' => $inv ? (float) ($inv->nominal ?? $this->keuangan->nilaiInvoice($p)) : null]];
        });

        return view('invoices.index-client', compact('projects', 'tagihan'));
    }

    public function show(Request $request, CastingProject $castingProject)
    {
        $this->pastikanBolehLihat($request, $castingProject);

        $invoice = $castingProject->invoices()->first();

        $castingProject->load('classes', 'applications.extras');

        $rincian = $this->keuangan->rincianInvoice($castingProject);

        return view('invoices.show', compact('castingProject', 'invoice', 'rincian'));
    }

    public function sign(Request $request, CastingProject $castingProject): RedirectResponse
    {
        $this->pastikanBolehLihat($request, $castingProject);

        $data = $request->validate(['signature' => ['required', 'string']]);

        $role = $request->user()->isClient() ? 'client' : 'admin';
        $invoice = $castingProject->invoices()->firstOrCreate([]);

        if ($invoice->{$role === 'client' ? 'ttd_client_signature_path' : 'ttd_admin_signature_path'} || $invoice->isLunas()) {
            return back()->with('error', 'Invoice ini sudah ditandatangani atau sudah lunas, tanda tangan tidak bisa diganti.');
        }

        $png = preg_match('#^data:image/png;base64,([A-Za-z0-9+/=\s]+)$#', $data['signature'], $m) ? base64_decode($m[1], true) : false;
        if ($png === false || strlen($png) < 50 || ! str_starts_with($png, "\x89PNG")) {
            return back()->with('error', 'Tanda tangan tidak valid atau masih kosong. Coba tanda tangan lagi.');
        }

        $filename = "invoices/signatures/{$castingProject->id}-{$role}-".Str::random(8).'.png';
        Storage::disk('local')->put($filename, $png);

        $invoice->update([
            $role === 'client' ? 'ttd_client_signature_path' : 'ttd_admin_signature_path' => $filename,
        ]);

        if ($invoice->ttd_admin_signature_path && $invoice->ttd_client_signature_path) {
            $castingProject->load('classes', 'applications.extras');
            $rincian = $this->keuangan->rincianInvoice($castingProject);
            $path = "invoices/pdf/{$castingProject->id}.pdf";
            $this->pdfGenerator->generate('invoices.pdf-template', compact('castingProject', 'invoice', 'rincian'), $path);
            $invoice->update(['pdf_path' => $path]);
        }

        ActivityLog::record(
            'SIGN_INVOICE',
            "{$request->user()->label()} {$request->user()->name} menandatangani invoice untuk proyek '{$castingProject->nama_produksi}'",
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

        abort_unless($user->bisaSebagaiAdmin() || $user->isClient(), 403);

        if ($user->isClient()) {
            abort_unless($castingProject->milikClient($user), 403);
            abort_unless($castingProject->client_request_status === 'disetujui', 404);
        }
    }
}
