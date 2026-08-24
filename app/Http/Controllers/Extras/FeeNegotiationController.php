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
        $this->pastikanMasihBisaNego($application);

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
        $this->pastikanMasihBisaNego($application);

        $data = $request->validate([
            'nominal' => ['required', 'numeric', 'min:0'],
        ]);

        $application->counterFee('extras', $data['nominal']);

        return back()->with('status', 'Counter fee terkirim ke Admin.');
    }

    private function pastikanMilikSendiri(Request $request, ProjectApplication $application): void
    {
        abort_unless(
            $application->extras_id === $request->user()->extrasProfile->id,
            403
        );
    }

    private function pastikanMasihBisaNego(ProjectApplication $application): void
    {
        abort_if(
            in_array($application->status_partisipasi, ['deal', 'ditolak', 'diajukan_ke_cd', 'direview_cd', 'lolos'], true),
            422,
            'Negosiasi untuk pendaftar ini sudah tidak aktif.'
        );
    }
}
