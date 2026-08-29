<?php

namespace App\Http\Controllers;

use App\Models\ProjectApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Diakses lintas role (Admin Default & Extras) untuk resource yang sama —
 * pola konsisten dengan ContractController/InvoiceController.
 */
class PaymentController extends Controller
{
    public function show(Request $request, ProjectApplication $application)
    {
        $this->pastikanBolehLihat($request, $application);

        if (! $application->payment) {
            $application->payment()->create(['status' => 'belum_dibayar']);
        }

        $application->load('payment.addons', 'extras', 'castingProject');

        return view('payments.show', compact('application'));
    }

    /**
     * RF-28: Admin Default menandai status "Sudah Ditransfer" + unggah
     * bukti transfer, disimpan di private disk (bukan public root).
     */
    public function tandaiTransfer(Request $request, ProjectApplication $application): RedirectResponse
    {
        abort_unless($request->user()->role === 'admin_default', 403);

        $request->validate([
            'bukti_transfer' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $path = $request->file('bukti_transfer')->store('payments/bukti-transfer', 'local');

        $application->payment->tandaiDitransfer($path);

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

        abort_unless($application->payment->status === 'ditransfer', 422, 'Belum ada bukti transfer untuk dikonfirmasi.');

        $application->payment->konfirmasiDiterima();
        $application->update(['status_partisipasi' => 'selesai_produksi']);

        return back()->with('status', 'Terima kasih, pembayaran telah dikonfirmasi.');
    }

    /**
     * RF-32: add-on/reimburse manual pada catatan pembayaran Extras.
     * Admin Default (siapa pun) atau Extras pemilik aplikasi bisa menambahkan.
     */
    public function addAddon(Request $request, ProjectApplication $application): RedirectResponse
    {
        $this->pastikanBolehLihat($request, $application);

        abort_if($application->payment->status === 'dikonfirmasi_diterima', 422, 'Pembayaran sudah selesai, tidak bisa menambah komponen lagi.');

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

    private function pastikanBolehLihat(Request $request, ProjectApplication $application): void
    {
        $user = $request->user();

        $boleh = $user->role === 'admin_default'
            || ($user->role === 'extras' && $application->extras_id === $user->extrasProfile->id);

        abort_unless($boleh, 403);
    }
}
