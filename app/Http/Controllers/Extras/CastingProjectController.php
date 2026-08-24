<?php

namespace App\Http\Controllers\Extras;

use App\Http\Controllers\Controller;
use App\Models\CastingProject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CastingProjectController extends Controller
{
    /**
     * RF-11: Extras melihat daftar proyek casting yang dibuka, diurutkan
     * berdasarkan fee tertinggi (proxy: budget_client kelas tertinggi di
     * proyek itu) dan status urgent di posisi teratas.
     */
    public function index()
    {
        $projects = CastingProject::where('status', 'dibuka')
            ->with('classes', 'shootingDates')
            ->get()
            ->sortByDesc(fn ($p) => [$p->is_urgent, $p->classes->max('budget_client')])
            ->values();

        return view('extras.projects.index', compact('projects'));
    }

    public function show(CastingProject $castingProject)
    {
        $castingProject->load('classes', 'shootingDates');

        return view('extras.projects.show', compact('castingProject'));
    }

    /**
     * RF-12/RF-13: Extras mendaftar (bisa paralel ke beberapa proyek).
     * Sistem mengecek bentrok jadwal dan menampilkan warning non-blocking,
     * BUKAN memblokir pendaftaran.
     */
    public function apply(Request $request, CastingProject $castingProject): RedirectResponse
    {
        $profile = $request->user()->extrasProfile;

        if ($castingProject->applications()->where('extras_id', $profile->id)->exists()) {
            return back()->with('status', 'Kamu sudah mendaftar ke proyek ini sebelumnya.');
        }

        $tanggalProyekIni = $castingProject->shootingDates->pluck('tanggal')
            ?: $castingProject->shootingDates()->pluck('tanggal');

        $tanggalBentrok = $profile->activeShootingDates()->intersect($tanggalProyekIni);
        $adaBentrok = $tanggalBentrok->isNotEmpty();

        $castingProject->applications()->create([
            'extras_id' => $profile->id,
            'status_partisipasi' => 'diajukan',
            'bentrok_jadwal_flag' => $adaBentrok,
        ]);

        $pesan = $adaBentrok
            ? '⚠️ Pendaftaran berhasil, tapi ada tanggal yang bertabrakan dengan proyek lain yang sedang kamu ikuti. Silakan cek kembali komitmenmu.'
            : 'Pendaftaran berhasil! Admin akan mereview profilmu.';

        return redirect()->route('extras.dashboard')->with('status', $pesan);
    }
}
