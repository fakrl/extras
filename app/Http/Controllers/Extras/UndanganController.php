<?php

namespace App\Http\Controllers\Extras;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ProjectApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** CE: Extras menjawab undangan proyek dari Admin. */
class UndanganController extends Controller
{
    public function terima(Request $request, ProjectApplication $application): RedirectResponse
    {
        $this->pastikanMilikSendiri($request, $application);
        $halaman = redirect()->route('extras.projects.show', $application->castingProject);
        if ($application->status_partisipasi !== 'diundang') {
            return $halaman->with('error', 'Undangan ini sudah tidak aktif.');
        }

        // BK.4: sama dengan apply: bentrok pasti ditolak, yang masih proses butuh konfirmasi.
        $bentrok = $application->extras->pendaftaranBentrok($application->tanggalShooting(), $application->id);
        $tanggal = fn ($b) => $b->tanggalBentrok->map(fn ($t) => Carbon::parse($t)->translatedFormat('d M Y'))->join(', ');
        if ($pasti = $bentrok->first(fn ($b) => $b->isPasti())) {
            return $halaman->with('error', "Kamu sudah terjadwal syuting {$pasti->castingProject->nama_produksi} tanggal {$tanggal($pasti)}. Batalkan dulu yang itu kalau mau ikut proyek ini.");
        }
        if ($bentrok->isNotEmpty() && ! $request->boolean('konfirmasi_bentrok')) {
            return $halaman->with('konfirmasi_bentrok', 'Tanggal ini bentrok dengan '.$bentrok->map(fn ($b) => $b->castingProject->nama_produksi)->join(', ', ' dan ').' yang masih diproses. Kalau dua-duanya lolos, kamu wajib pilih salah satu.');
        }

        if (! $application->terimaUndangan($bentrok)) {
            return $halaman->with('error', 'Undangan ini sudah tidak aktif.');
        }
        ActivityLog::record('INVITE_ACCEPT', "Extras {$request->user()->name} menerima undangan proyek '{$application->castingProject->nama_produksi}'", $application);

        return redirect()->route('extras.dashboard')->with('status', 'Undangan diterima. Admin akan mereview profilmu.');
    }

    public function tolak(Request $request, ProjectApplication $application): RedirectResponse
    {
        $this->pastikanMilikSendiri($request, $application);
        $data = $request->validate(['alasan' => ['nullable', 'string', 'max:255']]);

        if (! $application->tolakUndangan($data['alasan'] ?? null)) {
            return back()->with('error', 'Undangan ini sudah tidak aktif.');
        }
        ActivityLog::record('INVITE_DECLINE', "Extras {$request->user()->name} menolak undangan proyek '{$application->castingProject->nama_produksi}'", $application);

        return redirect()->route('extras.dashboard')->with('status', 'Undangan ditolak.');
    }

    private function pastikanMilikSendiri(Request $request, ProjectApplication $application): void
    {
        abort_unless($application->extras_id === $request->user()->extrasProfile?->id, 403);
    }
}
