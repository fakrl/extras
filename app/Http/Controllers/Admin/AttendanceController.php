<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\CastingProject;
use App\Models\ProjectApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    /**
     * SPEC.md Bagian F: halaman absensi Korlap — ringkas, mobile-friendly,
     * tanpa aksi finansial/Grade/Nego/Batalkan. Proyek+tanggal shooting
     * dipilih lewat dropdown (query string), bukan lewat halaman
     * admin.projects.applicants yang gerbangnya admin_default murni.
     */
    public function index(Request $request)
    {
        $projects = CastingProject::orderByDesc('id')->get(['id', 'nama_produksi', 'client_ph']);

        $castingProject = $projects->isNotEmpty()
            ? CastingProject::with('shootingDates')->find($request->query('project', $projects->first()->id))
            : null;

        $shootingDate = null;
        $applicants = collect();

        if ($castingProject) {
            $shootingDate = $request->filled('tanggal')
                ? $castingProject->shootingDates->firstWhere('id', (int) $request->query('tanggal'))
                : $castingProject->shootingDates->first();

            if ($shootingDate) {
                $applicants = $castingProject->applications()
                    ->whereIn('status_partisipasi', ProjectApplication::STATUS_AKTIF)
                    ->with(['extras.user', 'castingProjectClass', 'attendances.divalidasiOleh'])
                    ->get();
            }
        }

        return view('admin.attendance.index', compact('projects', 'castingProject', 'shootingDate', 'applicants'));
    }

    public function store(Request $request, ProjectApplication $application): RedirectResponse
    {
        $data = $request->validate([
            'event_shooting_date_id' => ['required', 'integer'],
            'status' => ['required', 'in:hadir,tidak_hadir'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $shootingDate = $application->castingProject->shootingDates()->find($data['event_shooting_date_id']);
        abort_unless($shootingDate, 422, 'Tanggal shooting tidak valid untuk proyek ini.');

        $fotoPath = $request->hasFile('foto')
            ? $request->file('foto')->store('attendances', 'local')
            : null;

        $updatePayload = [
            'status' => $data['status'],
            'dicatat_oleh' => $request->user()->id,
            'catatan' => $data['catatan'] ?? null,
            'status_validasi' => 'tervalidasi',
            'divalidasi_oleh' => $request->user()->id,
            'divalidasi_at' => now(),
        ];
        if ($fotoPath) {
            $updatePayload['foto_path'] = $fotoPath;
        }

        Attendance::updateOrCreate(
            [
                'project_application_id' => $application->id,
                'event_shooting_date_id' => $shootingDate->id,
            ],
            $updatePayload
        );

        return back()->with('status', 'Absensi berhasil dicatat.');
    }

    /**
     * Hybrid Attendance: Extras selfie submit via camera onsite.
     */
    public function extrasCheckin(Request $request, ProjectApplication $application): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'extras' && $application->extras->user_id === $user->id, 403);
        abort_unless(in_array($application->status_partisipasi, ProjectApplication::STATUS_LOLOS_KE_ATAS, true), 403, 'Hanya kandidat lolos/kontrak yang dapat absen.');

        $data = $request->validate([
            'event_shooting_date_id' => ['required', 'integer'],
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $shootingDate = $application->castingProject->shootingDates()->find($data['event_shooting_date_id']);
        abort_unless($shootingDate, 422, 'Tanggal shooting tidak valid.');

        $fotoPath = $request->file('foto')->store('attendances', 'local');

        Attendance::updateOrCreate(
            [
                'project_application_id' => $application->id,
                'event_shooting_date_id' => $shootingDate->id,
            ],
            [
                'status' => 'hadir',
                'foto_path' => $fotoPath,
                'status_validasi' => 'menunggu',
                'dicatat_oleh' => $user->id,
                'catatan' => 'Selfie Absensi Extras (Hybrid On-Site)',
            ]
        );

        return back()->with('status', 'Foto selfie absensi terkirim! Menunggu konfirmasi Korlap di lapangan.');
    }

    public function validasi(Request $request, Attendance $attendance): RedirectResponse
    {
        abort_if($attendance->status_validasi === 'tervalidasi', 422, 'Sudah divalidasi.');

        $attendance->update([
            'status' => 'hadir',
            'status_validasi' => 'tervalidasi',
            'divalidasi_oleh' => $request->user()->id,
            'divalidasi_at' => now(),
        ]);

        return back()->with('status', 'Absensi berhasil divalidasi.');
    }

    public function tolakValidasi(Request $request, Attendance $attendance): RedirectResponse
    {
        $attendance->update([
            'status' => 'tidak_hadir',
            'status_validasi' => 'tervalidasi',
            'divalidasi_oleh' => $request->user()->id,
            'divalidasi_at' => now(),
            'catatan' => ($attendance->catatan ? $attendance->catatan.' | ' : '').'Ditolak Korlap di lokasi.',
        ]);

        return back()->with('status', 'Absensi ditolak & ditandai Tidak Hadir.');
    }

    public function fotoStream(Attendance $attendance): StreamedResponse
    {
        abort_unless($attendance->foto_path && Storage::disk('local')->exists($attendance->foto_path), 404);

        return Storage::disk('local')->response($attendance->foto_path);
    }

    public function cdFotoStream(Request $request, Attendance $attendance): StreamedResponse
    {
        $user = $request->user();
        $project = $attendance->projectApplication->castingProject;
        $isAssignedCd = $project->cdAssignments()->where('cd_user_id', $user->id)->exists();
        $isClientOwner = $project->client_id === $user->id;

        abort_unless(
            $user->isAdmin() || $user->isKorlap() || $isAssignedCd || $isClientOwner,
            403
        );
        abort_unless($attendance->foto_path && Storage::disk('local')->exists($attendance->foto_path), 404);

        return Storage::disk('local')->response($attendance->foto_path);
    }
}
