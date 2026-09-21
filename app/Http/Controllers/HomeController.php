<?php

namespace App\Http\Controllers;

use App\Models\CastingProject;
use App\Models\User;

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

        $castExtras = User::where('role', 'extras')
            ->whereHas('extrasProfile', fn ($q) => $q->whereNotNull('share_token')->whereNotNull('foto_profil_path'))
            ->with(['extrasProfile:id,user_id,foto_profil_path,share_token'])
            ->inRandomOrder()
            ->limit(12)
            ->get(['id', 'name', 'username']);

        return view('welcome', compact('proyekTerbuka', 'adaLebih', 'proyekSelesai', 'castExtras'));
    }
}
