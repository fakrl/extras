<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ExtrasCategory;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    /**
     * RF-05: Admin Default mengelola akun Client dan menonaktifkan akun Extras
     * yang bermasalah. Cakupan sengaja dibatasi ke dua role ini, Admin
     * Default TIDAK punya kewenangan menonaktifkan Admin lain (itu hak
     * Super Admin lewat modul Manajemen Karyawan, RF-40/RF-41, Sprint 5).
     */
    public function index()
    {
        $clients = User::where('role', 'client')->get();
        $extras = User::where('role', 'extras')
            ->with(['extrasProfile' => fn ($q) => $q->withProyekSelesai()->withBatalMendadak(), 'extrasProfile.user:id,username', 'extrasProfile.categories'])
            ->get();
        $tagGroups = ExtrasCategory::perGrup();

        $mangkrakCount = User::mangkrak()->count();

        return view('admin.users.index', compact('clients', 'extras', 'tagGroups', 'mangkrakCount'));
    }

    /**
     * Rule Prune: Akun >30 hari mangkrak (profil tidak lengkap dan 0 riwayat proyek).
     */
    public function pruneAbandoned(Request $request): RedirectResponse
    {
        $count = User::hapusMangkrak(User::mangkrak()->with('extrasProfile')->get());

        ActivityLog::record(
            'PRUNE_ABANDONED_USERS',
            "{$request->user()->label()} {$request->user()->name} membersihkan {$count} akun extras mangkrak (>30 hari tanpa profil & pendaftaran)",
            null,
            ['jumlah_akun_dihapus' => $count]
        );

        return back()->with('status', "Berhasil membersihkan {$count} akun extras mangkrak (>30 hari tanpa kelengkapan profil & 0 pendaftaran).");
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        abort_unless(
            in_array($user->role, ['client', 'extras'], true),
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
        abort_unless($user->role === 'extras' && $user->extrasProfile, 403);

        $data = $request->validate([
            'kategori_ids' => ['nullable', 'array'],
            'kategori_ids.*' => ['exists:extras_categories,id'],
            'tag_nama' => ['nullable', 'array'],
            'tag_nama.*' => ['nullable', 'string', 'max:100'],
        ]);

        $ids = ExtrasCategory::idsDariInput($data['tag_nama'] ?? [], $data['kategori_ids'] ?? [], $request->user());
        $ubah = $user->extrasProfile->categories()->sync($ids);
        $nama = fn ($ids) => ExtrasCategory::whereKey($ids)->pluck('nama')->all();
        if ($ubah['attached'] || $ubah['detached']) {
            ActivityLog::record(
                'UPDATE_EXTRAS_KATEGORI',
                "{$request->user()->label()} {$request->user()->name} mengubah tag @{$user->username}",
                $user,
                ['ditambah' => $nama($ubah['attached']), 'dihapus' => $nama($ubah['detached'])]
            );
        }

        return back()->with('status', 'Tag extras diperbarui.');
    }

    /** BH.2: Admin/SA nyalakan/matikan tampil di beranda, cuma kalau Extras sudah kasih izin. */
    public function toggleBeranda(User $user, Request $request): RedirectResponse
    {
        abort_unless($request->user()->bisaSebagaiAdmin() && $user->role === 'extras' && $user->extrasProfile, 403);
        $profile = $user->extrasProfile;
        $nyala = ! $profile->tampil_di_beranda;

        if ($nyala && ! $profile->izin_tampil_publik) {
            return back()->with('error', "@{$user->username} belum mengizinkan profilnya tampil di website.");
        }
        if ($nyala) {
            $profile->generateShareToken();
        }
        $profile->forceFill(['tampil_di_beranda' => $nyala, 'tampil_di_beranda_at' => $nyala ? now() : null])->save();

        ActivityLog::record(
            'TOGGLE_EXTRAS_BERANDA',
            "{$request->user()->label()} {$request->user()->name} ".($nyala ? 'menampilkan' : 'menyembunyikan')." @{$user->username} di beranda",
            $profile,
            ['tampil_di_beranda' => $nyala]
        );

        return back()->with('status', $nyala ? "@{$user->username} tampil di beranda." : "@{$user->username} disembunyikan dari beranda.");
    }

    /**
     * AQ.3: Admin/SA melihat profil lengkap Extras (read-only admin view).
     */
    public function showProfile(Request $request, User $user): View|RedirectResponse
    {
        abort_unless($user->role === 'extras', 403);
        $profile = $user->extrasProfile;
        if (! $profile) {
            return back()->with('error', 'Profil Extras belum dibuat untuk akun ini.');
        }
        $profile->load('user', 'categories');

        return view($request->ajax() || $request->boolean('partial') ? 'partials.profil-extras-app' : 'extras.profile-show', ['profile' => $profile, 'mode' => 'admin']);
    }
}
