<?php

namespace App\Http\Controllers\Extras;

use App\Http\Controllers\Controller;
use App\Models\ProjectApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FeeNegotiationController extends Controller
{
    public function show(Request $request, ProjectApplication $application)
    {
        $this->pastikanMilikSendiri($request, $application);

        $application->load('feeNegotiations', 'castingProject');

        return view('extras.negotiations.show', compact('application'));
    }

    /**
     * RF-17: Extras terima penawaran nominal yang sedang berjalan (nominal
     * terakhir dari admin), tanpa mengubah jumlahnya sendiri.
     */
    public function terima(Request $request, ProjectApplication $application): RedirectResponse
    {
        $this->pastikanMilikSendiri($request, $application);
        if ($alasan = $application->alasanTidakBisaMenjawab('extras')) {
            return back()->with('error', $alasan);
        }

        $nominalTerakhir = $application->feeNegotiations()->reorder('round', 'desc')->value('nominal');

        $application->terimaFee('extras', $nominalTerakhir);

        return back()->with('status', 'Fee disepakati! Kamu akan diajukan ke Client.');
    }

    /**
     * RF-17: Extras mengajukan counter dengan nominal berbeda, tanpa batas
     * jumlah putaran (mekanisme tawar-menawar bertingkat ala InDrive).
     */
    public function counter(Request $request, ProjectApplication $application): RedirectResponse
    {
        $this->pastikanMilikSendiri($request, $application);
        if (! $application->castingProject->nego_terbuka) {
            return back()->with('error', 'Fee proyek ini tetap dan tidak bisa ditawar.');
        }

        if ($alasan = $application->alasanTidakBisaMenjawab('extras')) {
            return back()->with('error', $alasan);
        }

        $data = $request->validate([
            'nominal' => ['required', 'numeric', 'min:1'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        $application->counterFee('extras', $data['nominal'], $data['catatan'] ?? null);

        return back()->with('status', 'Tawaran balik terkirim ke Admin.');
    }

    /**
     * EX-05: proyek fee tetap tidak bisa ditawar, Extras yang tidak setuju cukup menolak penawaran.
     */
    public function tolak(Request $request, ProjectApplication $application): RedirectResponse
    {
        $this->pastikanMilikSendiri($request, $application);
        if ($application->castingProject->nego_terbuka) {
            return back()->with('error', 'Fee proyek ini bisa dinego, ajukan counter atau batalkan pendaftaran.');
        }
        if (! $application->masihBisaNego() || ! $application->feeNegotiations()->exists()) {
            return back()->with('error', 'Tidak ada penawaran yang bisa ditolak.');
        }

        $data = $request->validate(['alasan' => ['nullable', 'string', 'max:500']]);

        $application->tolakNegosiasi('extras', $data['alasan'] ?? null);
        $application->castingProject->admin?->kabari(
            'Penawaran Fee Ditolak',
            "{$request->user()->name} menolak penawaran fee di proyek {$application->castingProject->namaKode()}.",
            route('admin.negotiations.show', $application),
            jenis: 'nego_fee',
        );

        return back()->with('status', 'Penawaran ditolak. Pendaftaran ini tidak dilanjutkan.');
    }

    /**
     * RF-33: Extras membatalkan pendaftarannya yang sudah Deal.
     */
    public function batalkan(Request $request, ProjectApplication $application): RedirectResponse
    {
        $this->pastikanMilikSendiri($request, $application);

        $data = $request->validate([
            'alasan' => ['required', 'string'],
        ]);

        try {
            $application->batalkan('extras', $data['alasan']);
        } catch (\LogicException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Pendaftaran dibatalkan.');
    }

    private function pastikanMilikSendiri(Request $request, ProjectApplication $application): void
    {
        abort_unless(
            $application->extras_id === $request->user()->extrasProfile->id,
            403
        );
    }
}
