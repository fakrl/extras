<?php

namespace App\Http\Controllers;

use App\Models\CastingProject;
use App\Models\User;

class HomeController extends Controller
{
    public function index()
    {
        $totalProyek = CastingProject::count();
        $jumlahAdmin = User::where('role', 'like', 'admin_%')->count();
        $jumlahExtras = User::where('role', 'extras')->count();

        $allTerbuka = CastingProject::where('status', 'dibuka')
            ->select(['id', 'nama_produksi', 'deadline', 'kuota', 'status'])
            ->orderBy('deadline')
            ->with('classes:id,casting_project_id,nama_kelas,kuota_kelas')
            ->get()
            ->filter(fn ($p) => $p->menerimaPendaftaran());

        $proyekTerbuka = $allTerbuka->take(6)->values();
        $adaLebih = $allTerbuka->count() > 6;

        $proyekSelesai = CastingProject::where('status', 'ditutup')
            ->select(['id', 'nama_produksi', 'poster_path'])
            ->latest()
            ->take(8)
            ->get();

        return view('welcome', compact('totalProyek', 'jumlahAdmin', 'jumlahExtras', 'proyekTerbuka', 'adaLebih', 'proyekSelesai'));
    }
}
