<?php

namespace App\Http\Controllers;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;

class HomeController extends Controller
{
    const CAST_MAKS = 12;

    const PORTO_MAKS = 8;

    const CAST_MUAT = 6;

    const PORTO_MUAT = 4;

    public function index()
    {
        $allTerbuka = CastingProject::where('status', 'dibuka')
            ->select(['id', 'nama_produksi', 'client_id', 'deadline', 'kuota', 'is_urgent', 'status', 'poster_path'])
            ->orderBy('deadline')
            ->with(['client:id,name,nama_perusahaan', 'classes:id,casting_project_id,nama_kelas,kuota_kelas', 'shootingDates:id,casting_project_id,tanggal'])
            ->get()
            ->filter(fn ($p) => $p->menerimaPendaftaran());

        $proyekTerbuka = $allTerbuka->take(6)->values();
        $adaLebih = $allTerbuka->count() > 6;

        $portofolio = CastingProject::where('tampil_portofolio', true)
            ->select(['id', 'nama_produksi', 'client_id', 'poster_path', 'portofolio_judul', 'portofolio_jenis', 'portofolio_tahun', 'tampilkan_nama_client'])
            ->with('client:id,name,nama_perusahaan')
            ->orderByDesc('portofolio_tahun')
            ->latest('updated_at')
            ->take(self::PORTO_MAKS)
            ->get();

        $castExtras = ExtrasProfile::tampilDiBeranda()
            ->with(['user:id,username', 'categories' => fn ($q) => $q->where('grup', 'Usia tampilan')])
            ->latest('tampil_di_beranda_at')
            ->limit(self::CAST_MAKS)
            ->get(['id', 'user_id', 'share_token', 'tampil_di_beranda_at']);
        if ($castExtras->count() < 4) {
            $castExtras = collect();
        }

        $castStatis = $castExtras->count() <= self::CAST_MUAT;
        $portoStatis = $portofolio->count() <= self::PORTO_MUAT;

        return view('welcome', compact('proyekTerbuka', 'adaLebih', 'portofolio', 'castExtras', 'castStatis', 'portoStatis'));
    }
}
