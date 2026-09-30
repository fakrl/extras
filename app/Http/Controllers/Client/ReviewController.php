<?php

namespace App\Http\Controllers\Client;

use App\Exports\ClientRiwayatExport;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\ClientReview;
use App\Models\ExtrasCategory;
use App\Models\ProjectApplication;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ReviewController extends Controller
{
    private const STATUS_TERLIHAT = ['diajukan_ke_client', 'lolos', 'kontrak_ditandatangani', 'selesai_produksi', 'ditolak'];

    public function index(Request $request)
    {
        $proyek = CastingProject::query()->milikClient($request->user())
            ->with(['applications' => function ($q) {
                $q->whereIn('status_partisipasi', self::STATUS_TERLIHAT);
            }])
            ->get()
            ->map(function ($p) {
                $apps = $p->applications;

                return [
                    'proyek' => $p,
                    'menunggu' => $apps->where('status_partisipasi', 'diajukan_ke_client')->count(),
                    'approved' => $apps->whereIn('status_partisipasi', ['lolos', 'kontrak_ditandatangani', 'selesai_produksi'])->count(),
                    'rejected' => $apps->where('status_partisipasi', 'ditolak')->count(),
                    'total' => $apps->count(),
                ];
            });

        return view('client.reviews.index', compact('proyek'));
    }

    public function review(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'application_ids' => ['required', 'array', 'min:1'],
            'application_ids.*' => ['exists:project_applications,id'],
            'keputusan' => ['required', 'in:approve,reject'],
            'grade_client' => ['nullable', 'required_if:keputusan,approve', 'in:A,B,C'],
        ]);

        $applications = ProjectApplication::whereIn('id', $data['application_ids'])
            ->where('status_partisipasi', 'diajukan_ke_client')
            ->whereHas('castingProject', fn ($q) => $q->milikClient($request->user()))
            ->with('extras.user', 'castingProject')
            ->get();

        foreach ($applications as $application) {
            $application->clientReviews()->create([
                'client_id' => $request->user()->id,
                'keputusan' => $data['keputusan'],
                'grade_client' => $data['grade_client'] ?? null,
            ]);

            $application->update([
                'status_partisipasi' => $data['keputusan'] === 'approve' ? 'lolos' : 'ditolak',
            ]);

            $application->kirimNotifikasiHasil();
            $application->siapkanKontrakDanPembayaran();
            if ($application->isPasti()) {
                $application->kabariBentrokPasti();
            }

            ActivityLog::record(
                $data['keputusan'] === 'approve' ? 'REVIEW_CANDIDATE_LOCK' : 'REVIEW_CANDIDATE_REJECT',
                "Client {$request->user()->name} me-{$data['keputusan']} kandidat extras {$application->extras->user->name} untuk proyek {$application->castingProject->nama_produksi}",
                $application,
                ['grade_client' => $data['grade_client'] ?? null]
            );
        }

        $jumlah = $applications->count();
        $aksi = $data['keputusan'] === 'approve' ? 'disetujui' : 'ditolak';

        return back()->with('status', "{$jumlah} kandidat berhasil {$aksi}.");
    }

    public function show(Request $request, CastingProject $castingProject): Response
    {
        abort_unless($castingProject->milikClient($request->user()), 403);

        $statusFilter = $request->query('status');
        $genderFilter = $request->query('gender');
        $usiaMin = $request->query('usia_min');
        $usiaMax = $request->query('usia_max');

        $query = ProjectApplication::where('casting_project_id', $castingProject->id)
            ->whereIn('status_partisipasi', self::STATUS_TERLIHAT)
            ->with([
                'extras' => fn ($q) => $q->select('id', 'user_id', 'usia', 'gender', 'tinggi_badan', 'berat_badan', 'ukuran_baju', 'riwayat_pengalaman', 'bahasa', 'foto_profil_path', 'video_profil_path')->withProyekSelesai(),
                'extras.user:id,username',
                'extras.categories',
                'castingProjectClass:id,nama_kelas,kriteria',
                'castingProjectClass.categories',
                'clientReviews' => fn ($q) => $q->where('client_id', $request->user()->id)->latest()->limit(1),
            ]);

        if ($statusFilter === 'menunggu') {
            $query->where('status_partisipasi', 'diajukan_ke_client');
        } elseif ($statusFilter === 'approved') {
            $query->whereIn('status_partisipasi', ['lolos', 'kontrak_ditandatangani', 'selesai_produksi']);
        } elseif ($statusFilter === 'rejected') {
            $query->where('status_partisipasi', 'ditolak');
        }

        if ($genderFilter) {
            $query->whereHas('extras', fn ($q) => $q->where('gender', $genderFilter));
        }

        if ($usiaMin !== null && $usiaMin !== '') {
            $query->whereHas('extras', fn ($q) => $q->where('usia', '>=', (int) $usiaMin));
        }

        if ($usiaMax !== null && $usiaMax !== '') {
            $query->whereHas('extras', fn ($q) => $q->where('usia', '<=', (int) $usiaMax));
        }

        $applications = $query->latest()->get();

        // Tag warna kulit kandidat ikut jadi chip filter (ganti select warna kulit lama).
        $tagDicari = ExtrasCategory::dicariDiProyek($castingProject->id)
            ->merge($applications->flatMap(fn ($a) => $a->extras->categories->where('grup', 'Warna kulit')))
            ->unique('id')->values();

        return response()->view('client.reviews.show', compact('applications', 'castingProject', 'statusFilter', 'genderFilter', 'usiaMin', 'usiaMax', 'tagDicari'));
    }

    /** BI.1: profil Extras versi Client, cuma kandidat yang sudah diajukan di proyek Client ini (D3 sementara). */
    public function profil(Request $request, User $user)
    {
        $profile = $user->extrasProfile;
        abort_unless($profile && $profile->applications()
            ->whereIn('status_partisipasi', self::STATUS_TERLIHAT)
            ->whereHas('castingProject', fn ($q) => $q->milikClient($request->user()))
            ->exists(), 403);
        $profile->load('user', 'categories');

        return view($request->ajax() || $request->boolean('partial') ? 'partials.profil-extras-app' : 'extras.profile-show', ['profile' => $profile, 'mode' => 'client']);
    }

    public function exportRiwayatXlsx(Request $request, CastingProject $castingProject)
    {
        abort_unless($castingProject->milikClient($request->user()), 403);

        return Excel::download(
            new ClientRiwayatExport($request->user()->id, $castingProject->id),
            'riwayat-'.Str::slug($castingProject->nama_produksi).'.xlsx'
        );
    }

    public function exportRiwayatPdf(Request $request, CastingProject $castingProject)
    {
        abort_unless($castingProject->milikClient($request->user()), 403);
        $klienId = $request->user()->id;
        $reviews = ClientReview::where('client_id', $klienId)
            ->whereHas('projectApplication', fn ($q) => $q->where('casting_project_id', $castingProject->id))
            ->with(['projectApplication.extras:id,user_id', 'projectApplication.extras.user:id,username'])
            ->latest()
            ->get();

        return Pdf::loadView('client.reviews.riwayat-pdf', compact('reviews', 'castingProject'))
            ->download('riwayat-'.Str::slug($castingProject->nama_produksi).'.pdf');
    }
}
