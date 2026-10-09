<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApplicantController extends Controller
{
    /**
     * RF-15: Admin Default menetapkan Grade (A/B/C), penilaian kualitas,
     * independen dari besaran fee (yang diatur lewat modul Negosiasi Fee
     * terpisah, Sprint 3). Status_partisipasi TIDAK diubah di sini,
     * penetapan grade tidak otomatis memindahkan status ke tahap berikutnya.
     */
    public function setGrade(Request $request, ProjectApplication $application): RedirectResponse
    {
        $data = $request->validate([
            'grade' => ['required', 'in:A,B,C'],
        ]);

        $error = $this->terapkanGrade($application, $data['grade']);
        $redirect = back()->withFragment('app-'.$application->id);

        return $error ? $redirect->with('error', $error) : $redirect->with('status', 'Grade berhasil ditetapkan.');
    }

    /**
     * AY.5.6: aksi massal Lineup, memakai logic single per item.
     */
    public function bulk(Request $request, CastingProject $castingProject): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'aksi' => ['required', 'in:grade,tolak'],
            'grade' => ['required_if:aksi,grade', 'nullable', 'in:A,B,C'],
            'alasan_tolak' => ['required_if:aksi,tolak', 'nullable', 'string', 'max:1000'],
        ]);

        $berhasil = 0;
        $gagal = 0;
        foreach ($castingProject->applications()->whereIn('id', $data['ids'])->get() as $application) {
            if ($data['aksi'] === 'grade') {
                $this->terapkanGrade($application, $data['grade']) ? $gagal++ : $berhasil++;

                continue;
            }
            try {
                $application->tolakDini($data['alasan_tolak']);
                $berhasil++;
            } catch (\LogicException) {
                $gagal++;
            }
        }

        return back()->with('status', "{$berhasil} kandidat diproses".($gagal ? ", {$gagal} dilewati (terkunci/sudah lewat tahap)." : '.'));
    }

    private function terapkanGrade(ProjectApplication $application, string $grade): ?string
    {
        if ($application->status_partisipasi === 'diundang') {
            return 'Kandidat yang masih diundang belum bisa dinilai.';
        }

        /** @var ExtrasProfile $profile */
        $profile = $application->extras;

        if ($profile->grade_diberikan_at && now()->lt($profile->grade_diberikan_at->addMonths(2))) {
            $terkunciSampai = $profile->grade_diberikan_at->addMonths(2)->translatedFormat('d F Y');

            return "Grade masih terkunci sampai {$terkunciSampai}, gak bisa diubah dulu.";
        }

        $gradeLama = $profile->grade_saat_ini;
        $profile->update([
            'grade_saat_ini' => $grade,
            'grade_diberikan_at' => now(),
        ]);

        $updateData = ['grade' => $grade];
        if ($application->status_partisipasi === 'diajukan') {
            $updateData['status_partisipasi'] = 'direview_admin';
        }
        $application->update($updateData);

        ActivityLog::record(
            'SET_EXTRAS_GRADE',
            "Admin menetapkan Grade {$grade} pada profil extras {$profile->user->name}",
            $profile,
            [
                'extras_user_id' => $profile->user_id,
                'grade_lama' => $gradeLama,
                'grade_baru' => $grade,
                'terkunci_sampai' => now()->addMonths(2)->translatedFormat('d F Y'),
            ]
        );

        return null;
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

        $redirect = back()->withFragment('app-'.$application->id);
        try {
            $application->tolakDini($data['alasan_tolak']);
        } catch (\LogicException $e) {
            return $redirect->with('status', $e->getMessage());
        }

        return $redirect->with('status', 'Kandidat ditolak. Slot ini bisa diisi pendaftar lain.');
    }

    /**
     * RF-35: Korlap (atau Admin Default) mencatat catatan/sanksi lapangan
     * untuk kandidat. Murni informasional, tidak ada edit/delete (belum diminta).
     */
    public function tambahCatatan(Request $request, ProjectApplication $application): RedirectResponse
    {
        $data = $request->validate([
            'jenis' => ['required', 'in:catatan,sanksi'],
            'isi' => ['required', 'string', 'max:1000'],
        ]);

        $application->tambahCatatan($request->user(), $data['jenis'], $data['isi']);

        return back()->withFragment('app-'.$application->id)->with('status', 'Catatan lapangan berhasil disimpan.');
    }

    /**
     * RF-54/BN.3: Admin/SA menandai / mencabut Favorit (kolom apresiasi) + catatan "kenapa favorit".
     * Murni catatan internal Admin, tidak terlihat oleh Extras maupun Client.
     */
    public function toggleApresiasi(Request $request, ProjectApplication $application): RedirectResponse
    {
        $data = $request->validate([
            'apresiasi_catatan' => ['nullable', 'string', 'max:1000'],
            'apresiasi' => ['nullable', 'boolean'],
        ]);

        $extras = $application->extras;
        $favorit = $request->has('apresiasi') ? $request->boolean('apresiasi') : ! $extras->apresiasi;
        $extras->setFavorit($favorit, $data['apresiasi_catatan'] ?? null, $request->user());

        return back()->withFragment('app-'.$application->id)->with('status', $favorit ? 'Ditandai sebagai Favorit.' : 'Dihapus dari Favorit.');
    }

    /**
     * Modul 1: Admin / Korlap override karakter, scene, dan jam callingan individual extras.
     */
    public function updateBreakdown(Request $request, ProjectApplication $application): RedirectResponse
    {
        $data = $request->validate([
            'karakter' => ['nullable', 'string', 'max:255'],
            'karakter_override' => ['nullable', 'string', 'max:255'],
            'keterangan_scene' => ['nullable', 'string', 'max:255'],
            'scene_override' => ['nullable', 'string', 'max:255'],
            'jam_callingan' => ['nullable', 'string'],
            'jam_callingan_override' => ['nullable', 'string'],
            'tipe_continuity' => ['nullable', 'in:continuity,free'],
            'tipe_continuity_override' => ['nullable', 'in:continuity,free'],
        ]);

        $updateData = [];
        if ($request->has('karakter') || $request->has('karakter_override')) {
            $updateData['karakter_override'] = $request->input('karakter', $request->input('karakter_override'));
        }
        if ($request->has('keterangan_scene') || $request->has('scene_override')) {
            $updateData['scene_override'] = $request->input('keterangan_scene', $request->input('scene_override'));
        }
        if ($request->has('jam_callingan') || $request->has('jam_callingan_override')) {
            $updateData['jam_callingan_override'] = $request->input('jam_callingan', $request->input('jam_callingan_override'));
        }
        if ($request->has('tipe_continuity') || $request->has('tipe_continuity_override')) {
            $updateData['tipe_continuity_override'] = $request->input('tipe_continuity', $request->input('tipe_continuity_override'));
        }

        $application->update($updateData);

        ActivityLog::record(
            'UPDATE_LINEUP_BREAKDOWN',
            "Admin/Korlap memperbarui breakdown karakter/callingan untuk {$application->extras->user->name} di proyek {$application->castingProject->nama_produksi}",
            $application
        );

        return back()->withFragment('app-'.$application->id)->with('status', 'Detail breakdown karakter, scene, dan jam callingan berhasil diperbarui.');
    }
}
