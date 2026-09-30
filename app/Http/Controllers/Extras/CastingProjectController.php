<?php

namespace App\Http\Controllers\Extras;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\CastingProjectClass;
use App\Models\ProjectApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CastingProjectController extends Controller
{
    /**
     * RF-11: Extras melihat semua proyek casting.
     * Aktif dulu (urut deadline terdekat), selesai/ditutup paling bawah.
     */
    public function index(Request $request)
    {
        $peran = fn ($q) => $q->withTerisi()->with('categories:id,nama');
        $aktif = CastingProject::lowonganTerbuka();

        $selesai = CastingProject::where('status', 'ditutup')
            ->withCount(['applications as terisi' => fn ($q) => $q->whereIn('status_partisipasi', ['lolos', 'kontrak_ditandatangani', 'selesai_produksi'])])
            ->with(['classes' => $peran, 'shootingDates'])
            ->orderByDesc('deadline')
            ->get();

        $tagSaya = $this->tagSaya($request);

        return view('extras.projects.index', compact('aktif', 'selesai', 'tagSaya'));
    }

    public function show(Request $request, CastingProject $castingProject)
    {
        $castingProject->load(['classes' => fn ($q) => $q->withTerisi()->with('categories:id,nama'), 'shootingDates']);
        $tagSaya = $this->tagSaya($request);
        $bentrok = $request->user()->extrasProfile?->pendaftaranBentrok($castingProject->shootingDates->pluck('tanggal'))
            ->where('casting_project_id', '!=', $castingProject->id) ?? collect();

        return view('extras.projects.show', compact('castingProject', 'tagSaya', 'bentrok'));
    }

    private function tagSaya(Request $request): array
    {
        return $request->user()->extrasProfile?->categories->modelKeys() ?? [];
    }

    /**
     * RF-12/RF-13: Extras mendaftar (bisa paralel ke beberapa proyek).
     * BK.4: bentrok dengan pendaftaran pasti (lolos/kontrak) ditolak; dengan yang masih
     * proses boleh setelah konfirmasi (konfirmasi_bentrok=1), flag di dua-duanya.
     */
    public function apply(Request $request, CastingProject $castingProject): RedirectResponse
    {
        $profile = $request->user()->extrasProfile;

        if (! $castingProject->menerimaPendaftaran()) {
            return back()->with('error', 'Proyek ini sudah tidak menerima pendaftaran (ditutup, kuota penuh, atau lewat deadline).');
        }

        if (! $profile?->profilLengkap()) {
            return redirect()->route('extras.profile.edit')
                ->with('error', 'Lengkapi profil dulu sebelum mendaftar: foto utama, usia, gender, dan tinggi badan.');
        }

        if ($castingProject->applications()->where('extras_id', $profile->id)->exists()) {
            return back()->with('status', 'Kamu sudah mendaftar ke proyek ini sebelumnya.');
        }

        $kelasId = null;

        if ($castingProject->classes()->exists()) {
            $data = $request->validate(['casting_project_class_id' => 'required|integer']);
            $kelasId = $castingProject->classes()->findOrFail($data['casting_project_class_id'])->id;
        }

        // BK.4: bentrok dengan yang sudah pasti -> tolak; dengan yang masih proses -> konfirmasi dulu.
        $bentrok = $profile->pendaftaranBentrok($castingProject->shootingDates()->pluck('tanggal'));
        $namaTanggal = fn ($b) => $b->tanggalBentrok->map(fn ($t) => Carbon::parse($t)->translatedFormat('d M Y'))->join(', ');

        if ($pasti = $bentrok->first(fn ($b) => $b->isPasti())) {
            return back()->with('error', "Kamu sudah terjadwal syuting {$pasti->castingProject->nama_produksi} tanggal {$namaTanggal($pasti)}. Batalkan dulu yang itu kalau mau ikut proyek ini.");
        }

        if ($bentrok->isNotEmpty() && ! $request->boolean('konfirmasi_bentrok')) {
            return back()->withInput()->with('konfirmasi_bentrok', 'Tanggal ini bentrok dengan '.$bentrok->map(fn ($b) => $b->castingProject->nama_produksi)->join(', ', ' dan ').' yang masih diproses. Kalau dua-duanya lolos, kamu wajib pilih salah satu.');
        }

        $adaBentrok = $bentrok->isNotEmpty();

        // BE.4: kunci baris peran lalu hitung ulang terisi, biar slot terakhir nggak diisi dua orang.
        $application = DB::transaction(function () use ($castingProject, $profile, $kelasId, $adaBentrok, $bentrok) {
            if ($kelasId) {
                CastingProjectClass::whereKey($kelasId)->lockForUpdate()->first();

                if (CastingProjectClass::withTerisi()->find($kelasId)->sisaKuota() === 0) {
                    return null;
                }
            }

            ProjectApplication::whereKey($bentrok->modelKeys())->update(['bentrok_jadwal_flag' => true]);

            return $castingProject->applications()->create([
                'extras_id' => $profile->id,
                'casting_project_class_id' => $kelasId,
                'status_partisipasi' => 'diajukan',
                'bentrok_jadwal_flag' => $adaBentrok,
            ]);
        });

        if (! $application) {
            return back()->with('error', 'Kuota peran ini sedang penuh. Cek lagi nanti — slot bisa terbuka kalau ada pendaftar yang mundur.');
        }

        $application->kirimKonfirmasiApply();

        ActivityLog::record(
            'APPLY_PROJECT',
            "Extras {$request->user()->name} mendaftar ke proyek casting '{$castingProject->nama_produksi}'",
            $application
        );

        $pesan = $adaBentrok
            ? 'Pendaftaran berhasil. Ingat, jadwalnya bentrok dengan pendaftaranmu yang lain, kalau dua-duanya lolos kamu wajib pilih salah satu.'
            : 'Pendaftaran berhasil! Admin akan mereview profilmu.';

        return redirect()->route('extras.dashboard')->with('status', $pesan);
    }
}
