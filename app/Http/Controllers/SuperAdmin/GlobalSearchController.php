<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\CastingProject;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = $request->validate(['q' => 'required|string|min:2|max:100'])['q'];

        $proyek = CastingProject::where('nama_produksi', 'like', "%$q%")
            ->limit(5)->get(['id', 'nama_produksi', 'status']);

        $akun = User::where('name', 'like', "%$q%")
            ->orWhere('email', 'like', "%$q%")
            ->limit(5)->get(['id', 'name', 'email', 'role']);

        return response()->json([
            'proyek' => $proyek->map(fn ($p) => [
                'label' => $p->nama_produksi,
                'sub' => $p->status,
                'url' => route('admin.projects.index'),
            ]),
            'akun' => $akun->map(fn ($u) => [
                'label' => $u->name,
                'sub' => $u->email.' · '.$u->role,
                'url' => route('super-admin.admins.show', $u),
            ]),
        ]);
    }
}
