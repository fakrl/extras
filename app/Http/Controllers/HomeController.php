<?php

namespace App\Http\Controllers;

use App\Models\CastingProject;
use App\Models\ExtrasCategory;
use App\Models\ExtrasProfile;

class HomeController extends Controller
{
    public function index()
    {
        $allTerbuka = CastingProject::where('status', 'dibuka')
            ->select(['id', 'nama_produksi', 'client_ph', 'deadline', 'kuota', 'is_urgent', 'status', 'poster_path'])
            ->orderBy('deadline')
            ->with(['classes:id,casting_project_id,nama_kelas,kuota_kelas', 'shootingDates:id,casting_project_id,tanggal'])
            ->get()
            ->filter(fn ($p) => $p->menerimaPendaftaran());

        $proyekTerbuka = $allTerbuka->take(6)->values();
        $adaLebih = $allTerbuka->count() > 6;

        $proyekSelesai = CastingProject::where('status', 'ditutup')
            ->select(['id', 'nama_produksi', 'poster_path'])
            ->latest()
            ->take(8)
            ->get();

        $castExtras = ExtrasProfile::tampilDiBeranda()
            ->with(['user:id,username', 'categories' => fn ($q) => $q->whereIn('nama', ExtrasCategory::GRUP['Usia tampilan'])])
            ->latest('tampil_di_beranda_at')
            ->limit(16)
            ->get(['id', 'user_id', 'share_token', 'tampil_di_beranda_at']);
        if ($castExtras->count() < 4) {
            $castExtras = collect();
        }

        return view('welcome', compact('proyekTerbuka', 'adaLebih', 'proyekSelesai', 'castExtras'));
    }
}
