<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ProjectApplication;
use App\Notifications\InAppNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Diakses lintas role (Admin Default & Extras) untuk resource yang sama,
 * pola konsisten dengan ContractController/InvoiceController.
 */
class PaymentController extends Controller
{
    private function guardStatusLolos(ProjectApplication $application): void
    {
        abort_unless(
            in_array($application->status_partisipasi, ProjectApplication::STATUS_LOLOS_KE_ATAS, true),
            422,
            'Pembayaran belum bisa diproses untuk status pendaftaran ini.'
        );
    }

    public function show(Request $request, ProjectApplication $application)
    {
        abort_unless($application->bolehDilihatOleh($request->user()), 403);
        $this->guardStatusLolos($application);

        $application->load('payment.addons', 'extras', 'castingProject');

        return view('payments.show', compact('application'));
    }

    /**
     * RF-28: Admin Default menandai status "Sudah Ditransfer" + unggah
     * bukti transfer, disimpan di private disk (bukan public root).
     */
    public function tandaiTransfer(Request $request, ProjectApplication $application): RedirectResponse
    {
        abort_unless($request->user()->bisaSebagaiAdmin(), 403);
        $this->guardStatusLolos($application);

        if (! $application->payment) {
            return back()->with('error', 'Data pembayaran belum ada untuk pendaftaran ini.');
        }

        if ($application->payment->status !== 'belum_dibayar') {
            return back()->with('error', 'Transfer hanya bisa ditandai sekali, dari status Belum Dibayar.');
        }

        if ($application->payment->menungguKontrak()) {
            return back()->with('error', 'Transfer belum bisa ditandai, kontrak belum ditandatangani lengkap.');
        }

        $request->validate([
            'bukti_transfer' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $path = $request->file('bukti_transfer')->store('payments/bukti-transfer', 'local');

        $application->payment->tandaiDitransfer($path);

        ActivityLog::record(
            'UPLOAD_PAYOUT_TRANSFER',
            "{$request->user()->label()} {$request->user()->name} mengunggah bukti transfer honor untuk {$application->extras->user->name}",
            $application
        );

        $application->loadMissing('extras.user', 'castingProject');
        $extrasUser = $application->extras->user;
        $judulPay = 'Pembayaran Ditransfer';
        $pesanPay = "Honor kamu untuk proyek {$application->castingProject->namaKode()} sudah ditransfer. Silakan konfirmasi penerimaan.";
        try {
            $extrasUser->notify(new InAppNotification($judulPay, $pesanPay, route('payments.show', $application)));
        } catch (\Throwable) {
        }

        return back()->with('status', 'Status pembayaran ditandai "Sudah Ditransfer".');
    }

    /**
     * RF-29: Extras mengonfirmasi penerimaan pembayaran.
     */
    public function konfirmasi(Request $request, ProjectApplication $application): RedirectResponse
    {
        abort_unless(
            $request->user()->role === 'extras' && $application->extras_id === $request->user()->extrasProfile->id,
            403
        );
        $this->guardStatusLolos($application);

        if (! ($application->payment->status === 'ditransfer')) {
            return back()->with('error', 'Belum ada bukti transfer untuk dikonfirmasi.');
        }

        $application->payment->konfirmasiDiterima();
        $application->update(['status_partisipasi' => 'selesai_produksi']);

        ActivityLog::record(
            'CONFIRM_PAYMENT',
            "Extras {$request->user()->name} mengonfirmasi penerimaan honor (Lunas) untuk proyek '{$application->castingProject->nama_produksi}'",
            $application
        );

        return back()->with('status', 'Terima kasih, pembayaran telah dikonfirmasi.');
    }

    /**
     * RF-32: add-on/reimburse manual pada catatan pembayaran Extras.
     * Admin Default (siapa pun) atau Extras pemilik aplikasi bisa menambahkan.
     */
    public function addAddon(Request $request, ProjectApplication $application): RedirectResponse
    {
        abort_unless($application->bolehDilihatOleh($request->user()), 403);
        $this->guardStatusLolos($application);

        if ($application->payment->status === 'dikonfirmasi_diterima') {
            return back()->with('error', 'Pembayaran sudah selesai, tidak bisa menambah komponen lagi.');
        }

        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'nominal' => ['required', 'numeric', 'min:0'],
        ]);

        $application->payment->addons()->create([
            'label' => $data['label'],
            'nominal' => $data['nominal'],
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Komponen tambahan berhasil ditambahkan.');
    }

    public function buktiStream(Request $request, ProjectApplication $application): StreamedResponse
    {
        $user = $request->user();
        $isOwnerExtras = $user->role === 'extras' && $application->extras_id === $user->extrasProfile?->id;
        abort_unless($user->bisaSebagaiAdmin() || $isOwnerExtras, 403);

        $path = $application->payment?->bukti_transfer_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    public function sengketa(Request $request, ProjectApplication $application): RedirectResponse
    {
        $isExtrasOwner = $request->user()->role === 'extras'
            && $application->extras_id === $request->user()->extrasProfile?->id;

        abort_unless($isExtrasOwner || $request->user()->bisaSebagaiAdmin(), 403);
        $this->guardStatusLolos($application);

        if (! ($application->payment->status === 'ditransfer')) {
            return back()->with('error', 'Sengketa hanya bisa diajukan untuk pembayaran yang sudah ditransfer.');
        }

        $request->validate(['alasan' => ['required', 'string', 'max:500']]);

        $application->payment->tandaiDisengketakan($request->alasan);

        $aktor = "{$request->user()->label()} {$request->user()->name}";
        ActivityLog::record(
            'DISPUTE_PAYMENT',
            "{$aktor} menandai pembayaran sebagai sengketa untuk proyek '{$application->castingProject->nama_produksi}'",
            $application
        );

        return back()->with('status', 'Pembayaran ditandai sebagai sengketa. Admin akan menindaklanjuti.');
    }
}
