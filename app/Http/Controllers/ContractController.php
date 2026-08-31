<?php

namespace App\Http\Controllers;

use App\Mail\KontrakSiapTtdMail;
use App\Models\NotificationLog;
use App\Models\ProjectApplication;
use App\Services\PdfGeneratorService;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Controller ini dipakai bersama oleh Admin Default & Extras (dua pihak
 * yang tanda tangan kontrak yang sama) — makanya tidak ditaruh di
 * namespace Admin\ atau Extras\ terpisah. Middleware role tetap dicek
 * di routes/web.php, method di sini yang menegakkan siapa boleh apa.
 */
class ContractController extends Controller
{
    public function __construct(
        private PdfGeneratorService $pdfGenerator,
        private WhatsAppService $whatsapp,
    ) {}

    public function show(Request $request, ProjectApplication $application)
    {
        abort_unless($application->bolehDilihatOleh($request->user()), 403);

        if (! $application->contract) {
            // Gate sebelum auto-generate — kontrak PDF tidak boleh dibuat
            // sampai data yang muncul di dokumen lengkap: nama_asli (nama
            // penandatangan, diisi bareng alias di halaman profil) lalu NIK
            // (RF-04, sengaja di form terpisah demi data minimization;
            // rekening ikut ditawarkan di form itu tapi tidak diwajibkan).
            $kurang = match (true) {
                ! $application->extras->nama_asli => [
                    redirect()->route('extras.profile.edit'),
                    'Lengkapi Nama Asli (sesuai KTP) di profil dulu ya, itu yang dipakai di dokumen kontrak.',
                    'Extras belum melengkapi Nama Asli, kontrak belum bisa dibuat.',
                ],
                ! $application->extras->nik => [
                    redirect()->route('extras.kontrak.lengkapi-ktp', $application),
                    'Lengkapi NIK dulu ya, kontrak otomatis dibuat setelah itu.',
                    'Extras belum melengkapi NIK, kontrak belum bisa dibuat.',
                ],
                default => null,
            };

            if ($kurang) {
                [$tujuanExtras, $pesanExtras, $pesanAdmin] = $kurang;

                if ($request->user()->role === 'extras') {
                    return $tujuanExtras->with('info', $pesanExtras);
                }

                return redirect()->route('admin.projects.applicants', $application->castingProject)
                    ->with('error', $pesanAdmin);
            }

            abort_if($application->status_partisipasi !== 'lolos', 422, 'Kontrak hanya dibuat setelah Extras dinyatakan Lolos.');

            // RF-25: auto-generate dari data proyek, Extras, dan fee yang disepakati.
            $application->contract()->create([]);
            $this->renderPdf($application);
            $this->kirimNotifikasiKontrak($application);
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
        abort_unless($application->bolehDilihatOleh($request->user()), 403);

        $data = $request->validate([
            'signature' => ['required', 'string'],
        ]);

        $role = $request->user()->role === 'extras' ? 'extras' : 'admin';
        $filename = "contracts/signatures/{$application->id}-{$role}-".Str::random(8).'.png';

        $base64 = preg_replace('#^data:image/\w+;base64,#', '', $data['signature']);
        Storage::disk('local')->put($filename, base64_decode($base64));

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

    /**
     * RF-36: notif ke kedua pihak yang belum TTD begitu kontrak ter-generate.
     * Email adalah efek samping, kontrak sudah tersimpan sebelum ini dipanggil.
     */
    private function kirimNotifikasiKontrak(ProjectApplication $application): void
    {
        $application->loadMissing('extras.user', 'castingProject.admin');

        foreach ([$application->extras->user, $application->castingProject->admin] as $penerima) {
            try {
                Mail::to($penerima)->queue(new KontrakSiapTtdMail($application));
                NotificationLog::catat($penerima->id, 'kontrak_siap_ttd', true);
            } catch (\Throwable $e) {
                NotificationLog::catat($penerima->id, 'kontrak_siap_ttd', false);
            }

            $pesan = "Halo {$penerima->name}, kontrak untuk proyek {$application->castingProject->nama_produksi} sudah siap ditandatangani. Silakan cek sistem.";
            $this->whatsapp->kirimNotifikasi($penerima, 'kontrak_siap_ttd', $pesan);
        }
    }
}
