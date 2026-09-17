<?php

namespace App\Http\Controllers\Cd;

use App\Exports\CdRiwayatExport;
use App\Http\Controllers\Controller;
use App\Models\CastingProject;
use App\Models\CdReview;
use App\Models\ProjectApplication;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

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
            ->whereHas('castingProject.cdAssignments', fn ($q) => $q->where('cd_user_id', $request->user()->id))
            // extras.user dibatasi ke id+username saja — CD cuma butuh itu
            // buat tampilan "Alias (@username)", bukan kontak/email Extras.
            ->with('extras:id,user_id,foto_profil_path,video_profil_path', 'extras.user:id,username', 'extras.photos', 'castingProject:id,nama_produksi,link_grup', 'castingProjectClass:id,nama_kelas,kriteria')
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
            'grade_cd' => ['nullable', 'required_if:keputusan,approve', 'in:A,B,C'],
        ]);

        $bulkBatchId = count($data['application_ids']) > 1 ? Str::uuid()->toString() : null;

        $applications = ProjectApplication::whereIn('id', $data['application_ids'])
            ->where('status_partisipasi', 'diajukan_ke_cd')
            ->whereHas('castingProject.cdAssignments', fn ($q) => $q->where('cd_user_id', $request->user()->id))
            ->with('extras.user', 'castingProject')
            ->get();

        foreach ($applications as $application) {
            $application->cdReviews()->create([
                'cd_id' => $request->user()->id,
                'keputusan' => $data['keputusan'],
                'bulk_batch_id' => $bulkBatchId,
                'grade_cd' => $data['grade_cd'] ?? null,
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

    public function riwayat(Request $request)
    {
        $cdId = $request->user()->id;
        $reviews = CdReview::where('cd_id', $cdId)
            ->with('projectApplication.castingProject:id,nama_produksi')
            ->latest()
            ->get();

        $byProyek = $reviews->groupBy(fn ($r) => $r->projectApplication->casting_project_id)
            ->map(fn ($grup) => [
                'proyek' => $grup->first()->projectApplication->castingProject,
                'jumlah_approve' => $grup->where('keputusan', 'approve')->count(),
                'jumlah_reject' => $grup->where('keputusan', 'reject')->count(),
                'tanggal_terakhir' => $grup->max('created_at'),
            ])
            ->values();

        return view('cd.reviews.riwayat', compact('byProyek'));
    }

    public function riwayatProyek(Request $request, CastingProject $castingProject)
    {
        $cdId = $request->user()->id;
        $reviews = CdReview::where('cd_id', $cdId)
            ->whereHas('projectApplication', fn ($q) => $q->where('casting_project_id', $castingProject->id))
            ->with([
                'projectApplication.extras:id,user_id,usia,gender,tinggi_badan,ukuran_baju,warna_kulit,pengalaman,bahasa,foto_profil_path,video_profil_path',
                'projectApplication.extras.photos',
            ])
            ->latest()
            ->get();

        return view('cd.reviews.riwayat-proyek', compact('reviews', 'castingProject'));
    }

    public function exportRiwayatXlsx(Request $request, CastingProject $castingProject)
    {
        return Excel::download(
            new CdRiwayatExport($request->user()->id, $castingProject->id),
            'riwayat-'.Str::slug($castingProject->nama_produksi).'.xlsx'
        );
    }

    public function exportRiwayatPdf(Request $request, CastingProject $castingProject)
    {
        $cdId = $request->user()->id;
        $reviews = CdReview::where('cd_id', $cdId)
            ->whereHas('projectApplication', fn ($q) => $q->where('casting_project_id', $castingProject->id))
            ->with(['projectApplication.extras:id,user_id', 'projectApplication.extras.user:id,username'])
            ->latest()
            ->get();

        return Pdf::loadView('cd.reviews.riwayat-pdf', compact('reviews', 'castingProject'))
            ->download('riwayat-'.Str::slug($castingProject->nama_produksi).'.pdf');
    }
}
