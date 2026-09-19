<?php

namespace App\Http\Controllers\Extras;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ProjectApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttendanceSelfieController extends Controller
{
    public function store(Request $request, ProjectApplication $application): RedirectResponse
    {
        abort_unless(
            $application->extras_id === $request->user()->extrasProfile?->id,
            403
        );

        abort_unless(
            in_array($application->status_partisipasi, ProjectApplication::STATUS_AKTIF),
            403,
            'Proyek tidak sedang aktif.'
        );

        $request->validate([
            'foto' => ['required', 'image', 'max:5120'],
        ]);

        $path = $request->file('foto')->store(
            'absensi/' . $application->id,
            'local'
        );

        Attendance::create([
            'project_application_id' => $application->id,
            'event_shooting_date_id' => $application->castingProject
                ->shootingDates()
                ->whereDate('tanggal', today())
                ->value('id') ?? abort(422, 'Tidak ada jadwal shooting hari ini untuk proyek ini.'),
            'status' => 'hadir',
            'dicatat_oleh' => $request->user()->id,
            'foto_path' => $path,
            'status_validasi' => 'menunggu',
        ]);

        return back()->with('status', 'Selfie absensi berhasil dikirim, menunggu validasi Korlap.');
    }
}
