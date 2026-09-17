<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExtrasCategory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    /**
     * RF-05: Admin Default mengelola akun CD dan menonaktifkan akun Extras
     * yang bermasalah. Cakupan sengaja dibatasi ke dua role ini — Admin
     * Default TIDAK punya kewenangan menonaktifkan Admin lain (itu hak
     * Super Admin lewat modul Manajemen Karyawan, RF-40/RF-41, Sprint 5).
     */
    public function index()
    {
        $castingDirectors = User::where('role', 'casting_director')->get();
        $extras = User::where('role', 'extras')
            ->with('extrasProfile.user:id,username', 'extrasProfile.categories')
            ->get();
        $allCategories = ExtrasCategory::orderBy('nama')->get();

        return view('admin.users.index', compact('castingDirectors', 'extras', 'allCategories'));
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        abort_unless(
            in_array($user->role, ['casting_director', 'extras'], true),
            403,
            'Admin Default hanya boleh mengelola akun Casting Director dan Extras.'
        );

        $user->update([
            'status' => $user->status === 'aktif' ? 'nonaktif' : 'aktif',
        ]);

        return back()->with('status', "Status akun {$user->name} diperbarui.");
    }

    public function updateKategori(User $user, Request $request): RedirectResponse
    {
        abort_unless($user->role === 'extras', 403);

        $data = $request->validate([
            'kategori_ids' => ['nullable', 'array'],
            'kategori_ids.*' => ['exists:extras_categories,id'],
        ]);

        $user->extrasProfile?->categories()->sync($data['kategori_ids'] ?? []);

        return back()->with('status', 'Kategori extras diperbarui.');
    }
}
