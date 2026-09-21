<?php

namespace App\Http\Controllers;

use App\Models\CastingProject;

class HomeController extends Controller
{
    public function index()
    {
        $allTerbuka = CastingProject::where('status', 'dibuka')
            ->select(['id', 'nama_produksi', 'client_ph', 'deadline', 'kuota', 'is_urgent', 'status'])
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

        return view('welcome', compact('proyekTerbuka', 'adaLebih', 'proyekSelesai'));
    }
}
