<?php

namespace App\Http\Controllers\Cd;

use App\Http\Controllers\Controller;
use App\Models\ProjectApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReviewController extends Controller
{
    /**
     * Daftar kandidat yang statusnya "diajukan_ke_cd" — sudah Deal fee-nya,
     * siap direview CD. CD hanya melihat data lewat alias (tembok
     * visibilitas, lihat CLAUDE.md §5) — nama asli/NIK/fee/margin TIDAK
     * pernah dikirim ke view ini.
     */
    public function index(Request $request)
    {
        $applications = ProjectApplication::where('status_partisipasi', 'diajukan_ke_cd')
            // extras.user dibatasi ke id+username saja — CD cuma butuh itu
            // buat tampilan "Alias (@username)", bukan kontak/email Extras.
            ->with('extras:id,user_id,alias,foto_profil_path,video_profil_path', 'extras.user:id,username', 'extras.photos', 'castingProject:id,nama_produksi')
            ->latest()
            ->get();

        return view('cd.reviews.index', compact('applications'));
    }

    /**
     * RF-23: approve/reject individual atau massal. CD approve kecocokan
     * talent, BUKAN approve harga — fee sudah dikunci sebelum sampai di sini.
     */
    public function review(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'application_ids' => ['required', 'array', 'min:1'],
            'application_ids.*' => ['exists:project_applications,id'],
            'keputusan' => ['required', 'in:approve,reject'],
        ]);

        $bulkBatchId = count($data['application_ids']) > 1 ? Str::uuid()->toString() : null;

        $applications = ProjectApplication::whereIn('id', $data['application_ids'])
            ->where('status_partisipasi', 'diajukan_ke_cd')
            ->with('extras.user', 'castingProject')
            ->get();

        foreach ($applications as $application) {
            $application->cdReviews()->create([
                'cd_id' => $request->user()->id,
                'keputusan' => $data['keputusan'],
                'bulk_batch_id' => $bulkBatchId,
            ]);

            $application->update([
                'status_partisipasi' => $data['keputusan'] === 'approve' ? 'lolos' : 'ditolak',
            ]);

            $application->kirimNotifikasiHasil();
        }

        $jumlah = $applications->count();
        $aksi = $data['keputusan'] === 'approve' ? 'disetujui' : 'ditolak';

        return back()->with('status', "{$jumlah} kandidat berhasil {$aksi}.");
    }
}
