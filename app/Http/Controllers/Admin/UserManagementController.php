<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
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
        $castingDirectors = User::whereIn('role', ['client', 'casting_director'])->get();
        $extras = User::where('role', 'extras')
            ->with('extrasProfile.user:id,username', 'extrasProfile.categories')
            ->get();
        $allCategories = ExtrasCategory::orderBy('nama')->get();

        $mangkrakCount = User::where('role', 'extras')
            ->where('created_at', '<=', now()->subDays(30))
            ->whereDoesntHave('extrasProfile.applications')
            ->where(function ($q) {
                $q->whereDoesntHave('extrasProfile')
                    ->orWhereHas('extrasProfile', function ($ep) {
                        $ep->whereNull('foto_profil_path')->orWhereNull('nik');
                    });
            })
            ->count();

        return view('admin.users.index', compact('castingDirectors', 'extras', 'allCategories', 'mangkrakCount'));
    }

    /**
     * Rule Prune: Akun >30 hari mangkrak (profil tidak lengkap dan 0 riwayat proyek).
     */
    public function pruneAbandoned(Request $request): RedirectResponse
    {
        $abandonedUsers = User::where('role', 'extras')
            ->where('created_at', '<=', now()->subDays(30))
            ->whereDoesntHave('extrasProfile.applications')
            ->where(function ($q) {
                $q->whereDoesntHave('extrasProfile')
                    ->orWhereHas('extrasProfile', function ($ep) {
                        $ep->whereNull('foto_profil_path')->orWhereNull('nik');
                    });
            })
            ->get();

        $count = $abandonedUsers->count();
        foreach ($abandonedUsers as $u) {
            $u->extrasProfile?->categories()->detach();
            $u->extrasProfile?->forceDelete();
            $u->forceDelete();
        }

        ActivityLog::record(
            'PRUNE_ABANDONED_USERS',
            "Admin {$request->user()->name} membersihkan {$count} akun extras mangkrak (>30 hari tanpa profil & pendaftaran)",
            null,
            ['jumlah_akun_dihapus' => $count]
        );

        return back()->with('status', "Berhasil membersihkan {$count} akun extras mangkrak (>30 hari tanpa kelengkapan profil & 0 pendaftaran).");
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        abort_unless(
            in_array($user->role, ['client', 'casting_director', 'extras'], true),
            403,
            'Admin hanya boleh mengelola akun Client dan Extras.'
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
