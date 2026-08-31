<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

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
        $extras = User::where('role', 'extras')->with('extrasProfile.user:id,username')->get();

        return view('admin.users.index', compact('castingDirectors', 'extras'));
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
}
