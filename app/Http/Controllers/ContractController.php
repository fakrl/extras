<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ProjectApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Controller ini dipakai bersama oleh Admin Default & Extras (dua pihak
 * yang tanda tangan kontrak yang sama), makanya tidak ditaruh di
 * namespace Admin\ atau Extras\ terpisah. Middleware role tetap dicek
 * di routes/web.php, method di sini yang menegakkan siapa boleh apa.
 */
class ContractController extends Controller
{
    public function show(Request $request, ProjectApplication $application)
    {
        abort_unless($application->bolehDilihatOleh($request->user()), 403);

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
        abort_unless($application->bolehDilihatOleh($request->user()), 403);

        $contract = $application->contract;
        $role = $request->user()->role === 'extras' ? 'extras' : 'admin';
        $kolomTtd = $role === 'extras' ? 'ttd_extras_signature_path' : 'ttd_admin_signature_path';

        if (! $contract || $contract->isVoided() || $application->status_partisipasi !== 'lolos' || $contract->{$kolomTtd}) {
            return back()->with('error', 'Kontrak tidak bisa ditandatangani untuk status pendaftaran saat ini.');
        }

        // BK.4: Extras nggak bisa TTD dua kontrak di tanggal shooting yang sama.
        $bentrok = $role === 'extras'
            ? $application->extras->pendaftaranBentrok($application->tanggalShooting(), $application->id)
                ->first(fn ($b) => $b->status_partisipasi === 'kontrak_ditandatangani' || $b->contract?->ttd_extras_signature_path)
            : null;
        if ($bentrok) {
            $tgl = $bentrok->tanggalBentrok->map(fn ($t) => Carbon::parse($t)->translatedFormat('d M Y'))->join(', ');

            return back()
                ->with('error', "Kamu sudah tanda tangan kontrak {$bentrok->castingProject->nama_produksi} di tanggal yang sama ({$tgl}). Batalkan dulu yang itu kalau mau ikut proyek ini.")
                ->with('bentrok_link', route('extras.dashboard').'#pendaftaran-'.$bentrok->id);
        }

        $data = $request->validate([
            'signature' => ['required', 'string'],
        ]);

        $filename = "contracts/signatures/{$application->id}-{$role}-".Str::random(8).'.png';

        $base64 = preg_replace('#^data:image/\w+;base64,#', '', $data['signature']);
        Storage::disk('local')->put($filename, base64_decode($base64));

        $contract->update([$kolomTtd => $filename]);

        if ($contract->isFullySigned()) {
            $contract->update(['signed_at' => now()]);
            $application->update(['status_partisipasi' => 'kontrak_ditandatangani']);
            $application->renderKontrakPdf();
        }

        ActivityLog::record(
            'SIGN_CONTRACT',
            "{$request->user()->label()} {$request->user()->name} menandatangani kontrak kerja digital untuk proyek '{$application->castingProject->nama_produksi}'",
            $contract
        );

        return back()->with('status', 'Tanda tangan berhasil disimpan.');
    }

    public function downloadPdf(Request $request, ProjectApplication $application)
    {
        abort_unless($application->bolehDilihatOleh($request->user()), 403);
        abort_unless($application->contract, 404, 'Kontrak belum dibuat.');

        // Record sudah ada, file PDF cuma cache: render ulang kalau belum ada/hilang.
        if (! $application->contract->pdf_path || ! Storage::disk('local')->exists($application->contract->pdf_path)) {
            $application->renderKontrakPdf();
        }

        return Storage::disk('local')->download(
            $application->contract->pdf_path,
            'Kontrak-JBTB-'.Str::slug($application->castingProject->nama_produksi).'.pdf'
        );
    }
}
