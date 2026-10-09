<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** CE: Admin/SA mengundang Extras ke proyek (status `diundang`), lihat SPEC Bagian CE. */
class UndanganController extends Controller
{
    /** Isi dialog "Undang ke proyek…" (dimuat lewat fetch). */
    public function form(User $user)
    {
        abort_unless($user->isExtras() && $user->extrasProfile, 404);
        $profile = $user->extrasProfile;

        $proyek = CastingProject::untukUndangan()->with(['classes' => fn ($q) => $q->withTerisi(), 'shootingDates'])->latest()->get();
        $ada = $profile->applications()->pluck('status_partisipasi', 'casting_project_id');
        $bentrok = $proyek->mapWithKeys(fn ($p) => [$p->id => $profile->pendaftaranBentrok($p->shootingDates->pluck('tanggal'))]);

        return view('admin.undangan.form', compact('user', 'proyek', 'ada', 'bentrok'));
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isExtras() && $user->extrasProfile, 404);
        $profile = $user->extrasProfile;
        $data = $request->validate([
            'casting_project_id' => ['required', 'integer'],
            'casting_project_class_id' => ['nullable', 'integer'],
        ]);
        $project = CastingProject::with('classes', 'shootingDates')->findOrFail($data['casting_project_id']);
        $tolak = fn (string $pesan) => back()->with('error', $pesan);

        if (! $project->bisaDiundang()) {
            return $tolak('Proyek ini sudah selesai atau belum disetujui, tidak bisa mengundang.');
        }
        if ($ada = $project->applications()->where('extras_id', $profile->id)->first()) {
            return $tolak("@{$user->username} sudah punya pendaftaran di proyek ini (status: {$ada->label()}).");
        }
        if ($user->diblokir() || $profile->status !== 'aktif') {
            return $tolak("@{$user->username} nonaktif atau berstatus melanggar, tidak bisa diundang.");
        }

        $kelas = $project->classes->firstWhere('id', (int) ($data['casting_project_class_id'] ?? 0));
        if ($project->classes->isNotEmpty() && ! $kelas) {
            return $tolak('Pilih peran untuk proyek ini.');
        }
        $kelasId = $kelas?->id;

        // BK.4: sama dengan apply: bentrok pasti ditolak, yang masih proses butuh konfirmasi Admin.
        $bentrok = $profile->pendaftaranBentrok($project->shootingDates->pluck('tanggal'));
        $tanggal = fn ($b) => $b->tanggalBentrok->map(fn ($t) => Carbon::parse($t)->translatedFormat('d M Y'))->join(', ');
        if ($pasti = $bentrok->first(fn ($b) => $b->isPasti())) {
            return $tolak("@{$user->username} sudah terjadwal syuting {$pasti->castingProject->nama_produksi} tanggal {$tanggal($pasti)}, bentrok dengan proyek ini.");
        }
        if ($bentrok->isNotEmpty() && ! $request->boolean('konfirmasi_bentrok')) {
            return $tolak("Tanggalnya bentrok dengan {$bentrok->map(fn ($b) => $b->castingProject->nama_produksi)->join(', ', ' dan ')} yang masih diproses. Undang lagi dan konfirmasi untuk tetap lanjut.");
        }

        // BE.4: kunci peran (atau proyek bila tanpa peran) lalu hitung ulang slot.
        $application = DB::transaction(function () use ($project, $profile, $kelasId) {
            if ($kelasId) {
                $project->classes()->whereKey($kelasId)->lockForUpdate()->first();
                $penuh = $project->classes()->withTerisi()->find($kelasId)->sisaKuota() === 0;
            } else {
                CastingProject::whereKey($project->id)->lockForUpdate()->first();
                $penuh = $project->kuotaPenuh();
            }

            return $penuh ? null : $project->applications()->create([
                'extras_id' => $profile->id,
                'casting_project_class_id' => $kelasId,
                'status_partisipasi' => 'diundang',
                'diundang_at' => now(),
            ]);
        });

        if (! $application) {
            return $tolak('Kuota peran ini sudah penuh, tidak bisa mengundang.');
        }

        $application->kabariUndangan($request->user());
        ActivityLog::record('INVITE_EXTRAS', "{$request->user()->name} mengundang @{$user->username} ke proyek '{$project->nama_produksi}'", $application);

        return back()->with('status', "@{$user->username} diundang ke {$project->nama_produksi}. Hubungi via WA kalau perlu, jawabannya muncul di Lineup.");
    }

    public function batal(Request $request, ProjectApplication $application): RedirectResponse
    {
        $redirect = back()->withFragment('app-'.$application->id);
        if (! $application->batalkanUndangan()) {
            return $redirect->with('error', 'Undangan ini sudah dijawab atau dibatalkan.');
        }
        ActivityLog::record('INVITE_CANCEL', "{$request->user()->name} membatalkan undangan @{$application->extras->user->username} ke proyek '{$application->castingProject->nama_produksi}'", $application);

        return $redirect->with('status', 'Undangan dibatalkan. Slot kuota terbuka lagi.');
    }
}
