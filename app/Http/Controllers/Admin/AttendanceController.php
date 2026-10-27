<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\CastingProject;
use App\Models\EventShootingDate;
use App\Models\ProjectApplication;
use App\Models\User;
use App\Support\KorlapRingkasan;
use App\Support\Timeline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    /**
     * SPEC.md Bagian F: halaman absensi Korlap, ringkas, mobile-friendly,
     * tanpa aksi finansial/Grade/Nego/Batalkan. Proyek+tanggal shooting
     * dipilih lewat dropdown (query string), bukan lewat halaman
     * admin.projects.applicants yang gerbangnya admin murni.
     */
    public function index(Request $request)
    {
        $today = now()->toDateString();
        $user = $request->user();

        // Default proyek: yang punya shooting date paling dekat dengan hari ini
        $projects = CastingProject::where(fn ($w) => $w
            ->whereHas('shootingDates', fn ($q) => $q->where('tanggal', '>=', now()->subDay()->toDateString()))
            ->when($request->query('project'), fn ($q, $id) => $q->orWhere('id', $id)))
            ->dikelolaOleh($user)
            ->orderByDesc('id')
            ->with('client:id,name,nama_perusahaan')
            ->get(['id', 'nama_produksi', 'client_id', 'created_at']);

        $defaultProjectId = $request->query('project');
        if (! $defaultProjectId && $projects->isNotEmpty()) {
            // Cari proyek yang punya shooting date paling dekat/sama dengan hari ini
            $nearest = EventShootingDate::whereIn('casting_project_id', $projects->pluck('id'))
                ->where('tanggal', '>=', $today)
                ->orderBy('tanggal')
                ->first();
            $defaultProjectId = $nearest?->casting_project_id ?? $projects->first()->id;
        }

        $castingProject = $defaultProjectId
            ? CastingProject::with('shootingDates')->find($defaultProjectId)
            : null;
        if ($castingProject) {
            $this->pastikanBolehLihat($user, $castingProject);
        }

        $shootingDate = null;
        $applicants = collect();

        if ($castingProject) {
            if ($request->filled('tanggal')) {
                $shootingDate = $castingProject->shootingDates->firstWhere('id', (int) $request->query('tanggal'));
            } else {
                // Default: tanggal paling dekat/sama dengan hari ini
                $shootingDate = $castingProject->shootingDates->firstWhere(fn ($sd) => $sd->tanggal->toDateString() >= $today)
                    ?? $castingProject->shootingDates->first();
            }

            if ($shootingDate) {
                $applicants = KorlapRingkasan::peserta($castingProject, $shootingDate);
            }
        }

        $rekap = $shootingDate ? KorlapRingkasan::rekap($applicants) : null;

        // CR.1.3: timeline Hari ini (satu-satunya halaman dengan default beda), diperluas
        // supaya tetap mencakup tanggal yang sedang dipilih via ?tanggal= walau di luar skala.
        [$dari, $sampai, $skala] = Timeline::dariSesi($request, 'timeline_absensi', 'hari');
        $agendaDari = $dari;
        $agendaSampai = $sampai;
        if ($shootingDate) {
            $tgl = $shootingDate->tanggal->copy()->startOfDay();
            $agendaDari = $tgl->lt($agendaDari) ? $tgl->copy() : $agendaDari;
            $agendaSampai = $tgl->gt($agendaSampai) ? $tgl->copy() : $agendaSampai;
        }

        $agendaEvents = EventShootingDate::whereBetween('tanggal', [$agendaDari, $agendaSampai])
            ->whereHas('castingProject', fn ($q) => $q->dikelolaOleh($user))
            ->with('castingProject:id,nama_produksi')
            ->get()
            ->each(function ($e) {
                $e->nama_produksi = $e->castingProject?->nama_produksi;
                $e->url_proyek = route('admin.attendance.index', ['project' => $e->casting_project_id, 'tanggal' => $e->id]);
            });

        return view('admin.attendance.index', compact('projects', 'castingProject', 'shootingDate', 'applicants', 'rekap', 'today', 'agendaEvents', 'dari', 'sampai', 'skala'));
    }

    private function pastikanBolehProyek(Request $request, CastingProject $project): void
    {
        $this->authorize('kelola', $project);
    }

    /**
     * GET lihat-saja: Korlap tetap terbatas penugasannya (KR-01), tapi Admin/Super
     * Admin boleh melihat absensi proyek manapun (beda dari aksi tulis di CM.2.1).
     */
    private function pastikanBolehLihat(User $user, CastingProject $project): void
    {
        abort_unless(
            ! $user->isKorlap() || $project->adminAssignments()->where('user_id', $user->id)->exists(),
            403,
            'Proyek ini bukan tanggung jawab Anda.'
        );
    }

    public function store(Request $request, ProjectApplication $application): RedirectResponse
    {
        $this->pastikanBolehProyek($request, $application->castingProject);

        $data = $request->validate([
            'event_shooting_date_id' => ['required', 'integer'],
            'status' => ['required', 'in:hadir,tidak_hadir'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $shootingDate = $application->castingProject->shootingDates()->find($data['event_shooting_date_id']);
        if (! ($shootingDate)) {
            return back()->with('error', 'Tanggal shooting tidak valid untuk proyek ini.');
        }

        if (! in_array($application->status_partisipasi, ProjectApplication::STATUS_LOLOS_KE_ATAS, true)) {
            return back()->with('error', 'Absensi hanya untuk kandidat yang sudah lolos.');
        }

        if ($shootingDate->tanggal->toDateString() > today()->toDateString()) {
            return back()->with('error', 'Tanggal shooting ini belum tiba, absensi belum bisa dicatat.');
        }

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

        return back()->withFragment('app-'.$application->id)->with('status', 'Absensi berhasil dicatat.');
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
        if (! ($shootingDate)) {
            return back()->with('error', 'Tanggal shooting tidak valid.');
        }

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
        $this->pastikanBolehProyek($request, $attendance->projectApplication->castingProject);

        if ($attendance->status_validasi === 'tervalidasi') {
            return back()->with('error', 'Sudah divalidasi.');
        }

        $attendance->update([
            'status' => 'hadir',
            'status_validasi' => 'tervalidasi',
            'divalidasi_oleh' => $request->user()->id,
            'divalidasi_at' => now(),
        ]);

        ActivityLog::record(
            'VALIDATE_ATTENDANCE',
            "{$request->user()->label()} {$request->user()->name} memvalidasi kehadiran extras {$attendance->projectApplication->extras->user->name} di lokasi shooting",
            $attendance
        );

        return back()->withFragment('app-'.$attendance->project_application_id)->with('status', 'Absensi berhasil divalidasi.');
    }

    public function tolakValidasi(Request $request, Attendance $attendance): RedirectResponse
    {
        $this->pastikanBolehProyek($request, $attendance->projectApplication->castingProject);

        $data = $request->validate(['alasan' => ['required', 'string', 'max:500']]);

        $attendance->update([
            'status' => 'tidak_hadir',
            'status_validasi' => 'tervalidasi',
            'divalidasi_oleh' => $request->user()->id,
            'divalidasi_at' => now(),
            'catatan' => $data['alasan'],
        ]);

        ActivityLog::record(
            'REJECT_ATTENDANCE',
            "{$request->user()->label()} {$request->user()->name} menolak validasi absensi extras {$attendance->projectApplication->extras->user->name}",
            $attendance
        );

        return back()->withFragment('app-'.$attendance->project_application_id)->with('status', 'Validasi kehadiran ditolak (Status: Tidak Hadir).');
    }

    public function fotoStream(Request $request, Attendance $attendance): StreamedResponse
    {
        $this->pastikanBolehLihat($request->user(), $attendance->projectApplication->castingProject);
        abort_unless($attendance->foto_path && Storage::disk('local')->exists($attendance->foto_path), 404);

        return Storage::disk('local')->response($attendance->foto_path);
    }

    public function clientFotoStream(Request $request, Attendance $attendance): StreamedResponse
    {
        $user = $request->user();
        $project = $attendance->projectApplication->castingProject;

        abort_unless($user->bisaSebagaiAdmin() || $user->bisaSebagaiKorlap() || $project->milikClient($user), 403);
        abort_unless($attendance->foto_path && Storage::disk('local')->exists($attendance->foto_path), 404);

        return Storage::disk('local')->response($attendance->foto_path);
    }
}
