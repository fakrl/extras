<?php

namespace App\Http\Controllers\Extras;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\ProjectApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttendanceSelfieController extends Controller
{
    public function store(Request $request, ProjectApplication $application): RedirectResponse
    {
        abort_unless(
            $application->extras_id === $request->user()->extrasProfile?->id,
            403
        );

        if (! in_array($application->status_partisipasi, ProjectApplication::STATUS_LOLOS_KE_ATAS, true)) {
            return back()->with('error', 'Absen selfie hanya untuk proyek yang sudah kamu lolos.');
        }

        $request->validate([
            'foto' => ['required', 'image', 'max:5120'],
        ]);

        $shootingDateId = $application->castingProject->shootingDates()->whereDate('tanggal', today())->value('id');

        if (! $shootingDateId) {
            return back()->with('error', 'Hari ini bukan jadwal shooting proyek ini, absen selfie belum bisa dikirim.');
        }

        $existing = Attendance::where('project_application_id', $application->id)
            ->where('event_shooting_date_id', $shootingDateId)
            ->first();

        if ($existing && $existing->status_validasi !== 'menunggu') {
            return back()->with('error', 'Absensi hari ini sudah divalidasi Korlap.');
        }

        $path = $request->file('foto')->store(
            'absensi/'.$application->id,
            'local'
        );

        $attendance = Attendance::updateOrCreate(
            [
                'project_application_id' => $application->id,
                'event_shooting_date_id' => $shootingDateId,
            ],
            [
                'status' => 'hadir',
                'dicatat_oleh' => $request->user()->id,
                'foto_path' => $path,
                'status_validasi' => 'menunggu',
                'catatan' => 'Selfie Absensi Extras (Hybrid On-Site)',
            ]
        );

        ActivityLog::record(
            'SUBMIT_SELFIE_ATTENDANCE',
            "Extras {$request->user()->name} mengirimkan selfie absensi di lokasi shooting untuk proyek {$application->castingProject->nama_produksi}",
            $attendance
        );

        return back()->with('status', 'Selfie absensi berhasil dikirim, menunggu validasi Korlap di lokasi.');
    }
}
