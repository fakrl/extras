<?php

namespace App\Http\Controllers;

use App\Models\ProjectApplication;
use App\Services\PdfGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Controller ini dipakai bersama oleh Admin Default & Extras (dua pihak
 * yang tanda tangan kontrak yang sama) — makanya tidak ditaruh di
 * namespace Admin\ atau Extras\ terpisah. Middleware role tetap dicek
 * di routes/web.php, method di sini yang menegakkan siapa boleh apa.
 */
class ContractController extends Controller
{
    public function __construct(private PdfGeneratorService $pdfGenerator)
    {
    }

    public function show(Request $request, ProjectApplication $application)
    {
        $this->pastikanBolehLihat($request, $application);

        if (! $application->contract) {
            abort_if($application->status_partisipasi !== 'lolos', 422, 'Kontrak hanya dibuat setelah Extras dinyatakan Lolos.');

            // RF-25: auto-generate dari data proyek, Extras, dan fee yang disepakati.
            $application->contract()->create([]);
            $this->renderPdf($application);
        }

        $application->load('contract', 'extras', 'castingProject');

        return view('contracts.show', compact('application'));
    }

    /**
     * RF-26: canvas signature disematkan ke dokumen. Admin dan Extras
     * masing-masing tanda tangan lewat endpoint ini, base64 PNG dari
     * komponen signature-pad disimpan sebagai file gambar terpisah,
     * TIDAK sebagai e-signature tersertifikasi (PSrE).
     */
    public function sign(Request $request, ProjectApplication $application): RedirectResponse
    {
        $this->pastikanBolehLihat($request, $application);

        $data = $request->validate([
            'signature' => ['required', 'string'],
        ]);

        $role = $request->user()->role === 'extras' ? 'extras' : 'admin';
        $filename = "contracts/signatures/{$application->id}-{$role}-" . Str::random(8) . '.png';

        $base64 = preg_replace('#^data:image/\w+;base64,#', '', $data['signature']);
        \Illuminate\Support\Facades\Storage::disk('local')->put($filename, base64_decode($base64));

        $contract = $application->contract;
        $contract->update([
            $role === 'extras' ? 'ttd_extras_signature_path' : 'ttd_admin_signature_path' => $filename,
        ]);

        if ($contract->isFullySigned()) {
            $contract->update(['signed_at' => now()]);
            $application->update(['status_partisipasi' => 'kontrak_ditandatangani']);
            $this->renderPdf($application);
        }

        return back()->with('status', 'Tanda tangan berhasil disimpan.');
    }

    private function renderPdf(ProjectApplication $application): void
    {
        $application->load('contract', 'extras', 'castingProject');

        $path = "contracts/pdf/{$application->id}.pdf";

        $this->pdfGenerator->generate('contracts.pdf-template', [
            'application' => $application,
        ], $path);

        $application->contract->update(['pdf_path' => $path]);
    }

    private function pastikanBolehLihat(Request $request, ProjectApplication $application): void
    {
        $user = $request->user();

        $bolehLihat = $user->role === 'admin_default'
            || ($user->role === 'extras' && $application->extras_id === $user->extrasProfile->id);

        abort_unless($bolehLihat, 403);
    }
}
