<?php

namespace App\Http\Controllers\Cd;

use App\Exports\CdRiwayatExport;
use App\Http\Controllers\Controller;
use App\Models\CastingProject;
use App\Models\CdProjectAssignment;
use App\Models\CdReview;
use App\Models\ProjectApplication;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $cdId = $request->user()->id;

        $proyekIds = CdProjectAssignment::where('cd_user_id', $cdId)->pluck('casting_project_id');

        $proyek = CastingProject::whereIn('id', $proyekIds)
            ->with(['applications' => function ($q) {
                $q->whereIn('status_partisipasi', [
                    'diajukan_ke_cd', 'lolos', 'kontrak_ditandatangani', 'selesai_produksi', 'ditolak',
                ]);
            }])
            ->get()
            ->map(function ($p) {
                $apps = $p->applications;

                return [
                    'proyek' => $p,
                    'menunggu' => $apps->where('status_partisipasi', 'diajukan_ke_cd')->count(),
                    'approved' => $apps->whereIn('status_partisipasi', ['lolos', 'kontrak_ditandatangani', 'selesai_produksi'])->count(),
                    'rejected' => $apps->where('status_partisipasi', 'ditolak')->count(),
                    'total' => $apps->count(),
                ];
            });

        return view('cd.reviews.index', compact('proyek'));
    }

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

    public function show(Request $request, CastingProject $castingProject): Response
    {
        abort_unless(
            $castingProject->cdAssignments()->where('cd_user_id', $request->user()->id)->exists(),
            403
        );

        $statusFilter = $request->query('status');

        $query = ProjectApplication::where('casting_project_id', $castingProject->id)
            ->whereIn('status_partisipasi', [
                'diajukan_ke_cd', 'lolos', 'kontrak_ditandatangani', 'selesai_produksi', 'ditolak',
            ])
            ->with([
                'extras:id,user_id,usia,gender,tinggi_badan,ukuran_baju,warna_kulit,pengalaman,bahasa,foto_profil_path,video_profil_path',
                'extras.user:id,username',
                'extras.photos',
                'castingProjectClass:id,nama_kelas,kriteria',
                'cdReviews' => fn ($q) => $q->where('cd_id', $request->user()->id)->latest()->limit(1),
            ]);

        if ($statusFilter === 'menunggu') {
            $query->where('status_partisipasi', 'diajukan_ke_cd');
        } elseif ($statusFilter === 'approved') {
            $query->whereIn('status_partisipasi', ['lolos', 'kontrak_ditandatangani', 'selesai_produksi']);
        } elseif ($statusFilter === 'rejected') {
            $query->where('status_partisipasi', 'ditolak');
        }

        $applications = $query->latest()->get();

        return response()->view('cd.reviews.show', compact('applications', 'castingProject', 'statusFilter'));
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
