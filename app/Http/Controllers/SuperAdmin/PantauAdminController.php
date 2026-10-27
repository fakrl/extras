<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminRingkasan;
use Illuminate\Http\Request;

/**
 * CR.3.2: halaman detail Pantau Admin (dipindah dari dashboard, bukan dibuat
 * ulang) - reuse partial _pantau-admin, semua tugas (tanpa batas 5), filter Admin.
 */
class PantauAdminController extends Controller
{
    public function index(Request $request)
    {
        $adminsAktif = User::where('role', User::ROLE_ADMIN)->where('status', 'aktif')->orderBy('name')->get();

        $adminId = $request->integer('admin_id') ?: null;
        $dipilih = $adminId ? $adminsAktif->where('id', $adminId) : $adminsAktif;

        $pantauAdmin = AdminRingkasan::pantauAdmin($dipilih, null)
            ->sortBy(fn ($p) => $p['tugas']->min(fn ($t) => $t['sejak']?->timestamp ?? 0))
            ->values();

        return view('super-admin.pantau-admin', compact('pantauAdmin', 'adminsAktif', 'adminId'));
    }
}
