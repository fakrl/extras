<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProjectApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApplicantController extends Controller
{
    /**
     * RF-15: Admin Default menetapkan Grade (A/B/C) — penilaian kualitas,
     * independen dari besaran fee (yang diatur lewat modul Negosiasi Fee
     * terpisah, Sprint 3). Status_partisipasi TIDAK diubah di sini —
     * penetapan grade tidak otomatis memindahkan status ke tahap berikutnya.
     */
    public function setGrade(Request $request, ProjectApplication $application): RedirectResponse
    {
        $data = $request->validate([
            'grade' => ['required', 'in:A,B,C'],
        ]);

        $application->update([
            'grade' => $data['grade'],
            'status_partisipasi' => 'direview_admin',
        ]);

        return back()->with('status', 'Grade berhasil ditetapkan.');
    }

    /**
     * RF-15 (perluasan): reject dini kandidat yang jelas tidak sesuai
     * spesifikasi, sebelum masuk fase nego fee. Wajib isi alasan supaya
     * Extras tahu kenapa ditolak (lihat ProjectApplication::tolakDini()).
     */
    public function reject(Request $request, ProjectApplication $application): RedirectResponse
    {
        $data = $request->validate([
            'alasan_tolak' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $application->tolakDini($data['alasan_tolak']);
        } catch (\LogicException $e) {
            return back()->with('status', $e->getMessage());
        }

        return back()->with('status', 'Kandidat ditolak. Slot ini bisa diisi pendaftar lain.');
    }
}
