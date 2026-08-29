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
        $application->pastikanMasihBisaNego();

        $nominalTerakhir = $application->feeNegotiations()->latest('round')->value('nominal');

        $application->terimaFee('extras', $nominalTerakhir);

        return back()->with('status', 'Fee Deal! Kamu akan diajukan ke Casting Director.');
    }

    /**
     * RF-17: Extras mengajukan counter dengan nominal berbeda, tanpa batas
     * jumlah putaran (mekanisme tawar-menawar bertingkat ala InDrive).
     */
    public function counter(Request $request, ProjectApplication $application): RedirectResponse
    {
        $this->pastikanMilikSendiri($request, $application);
        $application->pastikanMasihBisaNego();

        $data = $request->validate([
            'nominal' => ['required', 'numeric', 'min:0'],
        ]);

        $application->counterFee('extras', $data['nominal']);

        return back()->with('status', 'Counter fee terkirim ke Admin.');
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
            return back()->with('status', $e->getMessage());
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
