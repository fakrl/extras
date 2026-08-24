<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProjectApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FeeNegotiationController extends Controller
{
    public function show(ProjectApplication $application)
    {
        $application->load('feeNegotiations', 'extras', 'castingProject.classes');

        return view('admin.negotiations.show', compact('application'));
    }

    /**
     * RF-16: Admin mengajukan penawaran fee awal berdasarkan rate card
     * Extras dan budget dari client. Hanya boleh dipanggil sekali per
     * aplikasi — ronde selanjutnya lewat counter().
     */
    public function ajukanAwal(Request $request, ProjectApplication $application): RedirectResponse
    {
        if ($application->feeNegotiations()->exists()) {
            return back()->with('status', 'Fee awal sudah pernah diajukan untuk pendaftar ini.');
        }

        $data = $request->validate([
            'nominal' => ['required', 'numeric', 'min:0'],
        ]);

        $application->ajukanFeeAwal($data['nominal']);

        return back()->with('status', 'Penawaran fee awal terkirim ke Extras.');
    }

    /**
     * RF-18: Admin counter balik atau terima counter dari Extras.
     */
    public function counter(Request $request, ProjectApplication $application): RedirectResponse
    {
        $data = $request->validate([
            'nominal' => ['required', 'numeric', 'min:0'],
        ]);

        $this->pastikanMasihBisaNego($application);

        $application->counterFee('admin', $data['nominal']);

        return back()->with('status', 'Counter fee terkirim.');
    }

    public function terima(Request $request, ProjectApplication $application): RedirectResponse
    {
        $data = $request->validate([
            'nominal' => ['required', 'numeric', 'min:0'],
        ]);

        $this->pastikanMasihBisaNego($application);

        $application->terimaFee('admin', $data['nominal']);

        return back()->with('status', 'Fee Deal! Nominal terkunci di ' . number_format($data['nominal'], 0, ',', '.'));
    }

    /**
     * RF-18: Admin menghentikan proses negosiasi.
     */
    public function tolak(ProjectApplication $application): RedirectResponse
    {
        $this->pastikanMasihBisaNego($application);

        $application->tolakNegosiasi('admin');

        return back()->with('status', 'Negosiasi fee dihentikan.');
    }

    /**
     * RF-21: hanya kandidat yang fee-nya sudah Deal yang boleh diajukan ke CD.
     * Method ajukanKeCd() di model sendiri sudah menjaga urutan ini —
     * di sini cukup tangkap exception-nya jadi pesan yang manusiawi.
     */
    public function ajukanKeCd(ProjectApplication $application): RedirectResponse
    {
        try {
            $application->ajukanKeCd();
        } catch (\LogicException $e) {
            return back()->with('status', $e->getMessage());
        }

        return back()->with('status', 'Kandidat diajukan ke Casting Director.');
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
