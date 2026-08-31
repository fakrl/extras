<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\CastingProject;
use App\Models\ProjectApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
                    ->with('extras', 'attendances')
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
        ]);

        $shootingDate = $application->castingProject->shootingDates()->find($data['event_shooting_date_id']);
        abort_unless($shootingDate, 422, 'Tanggal shooting tidak valid untuk proyek ini.');

        Attendance::updateOrCreate(
            [
                'project_application_id' => $application->id,
                'event_shooting_date_id' => $shootingDate->id,
            ],
            [
                'status' => $data['status'],
                'dicatat_oleh' => $request->user()->id,
                'catatan' => $data['catatan'] ?? null,
            ]
        );

        return back()->with('status', 'Absensi berhasil dicatat.');
    }
}
