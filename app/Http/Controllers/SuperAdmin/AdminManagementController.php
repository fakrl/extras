<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\ExtrasCategory;
use App\Models\ExtrasProfile;
use App\Models\User;
use App\Rules\NomorWa;
use App\Support\PerHalaman;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminManagementController extends Controller
{
    /**
     * BD.4: Manajemen Akun, satu daftar semua akun + filter popover.
     * "Sedang aktif di proyek": Extras punya aplikasi selain ditolak/dibatalkan/selesai_produksi,
     * Admin/Korlap punya penugasan status_log berjalan, Client ter-assign ke proyek mendatang/berjalan.
     */
    public function akun(Request $request)
    {
        $f = [
            'q' => trim((string) $request->query('q')),
            'role' => array_key_exists($request->query('role'), User::LABELS) ? $request->query('role') : null,
            'status' => in_array($request->query('status'), ['aktif', 'nonaktif', 'dihapus'], true) ? $request->query('status') : null,
            'sedang_aktif' => $request->boolean('sedang_aktif'),
            'akan_dihapus' => $request->boolean('akan_dihapus'),
            'tag' => array_filter(array_map('intval', (array) $request->query('tag', []))),
            'grade' => in_array($request->query('grade'), ['A', 'B', 'C', 'belum'], true) ? $request->query('grade') : null,
        ];

        $users = User::query()
            ->where('id', '!=', auth()->id())
            ->when($f['status'] === 'dihapus', fn ($q) => $q->onlyTrashed())
            ->when(in_array($f['status'], ['aktif', 'nonaktif'], true), fn ($q) => $q->where('status', $f['status']))
            ->when($f['role'], fn ($q, $role) => $q->where('role', $role))
            ->when($f['q'] !== '', function ($q) use ($f) {
                $like = "%{$f['q']}%";
                $wa = ltrim(preg_replace('/\D/', '', $f['q']), '0');
                $q->where(fn ($w) => $w->where('name', 'like', $like)
                    ->orWhere('username', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->when(strlen($wa) >= 4, fn ($w) => $w->orWhere('nomor_wa', 'like', "%{$wa}%")));
            })
            ->when($f['sedang_aktif'], fn ($q) => $q->where(fn ($w) => $w
                ->where(fn ($e) => $e->where('role', User::ROLE_EXTRAS)
                    ->whereHas('extrasProfile.applications', fn ($a) => $a->whereNotIn('status_partisipasi', ['ditolak', 'dibatalkan', 'selesai_produksi'])))
                ->orWhere(fn ($s) => $s->whereIn('role', [User::ROLE_ADMIN, User::ROLE_KORLAP])
                    ->whereHas('adminProjectAssignments', fn ($a) => $a->where('status_log', 'berjalan')))
                ->orWhere(fn ($c) => $c->where('role', User::ROLE_CLIENT)
                    ->whereHas('proyekClient', fn ($p) => $p
                        ->where(fn ($t) => $t->diTahap('mendatang'))
                        ->orWhere(fn ($t) => $t->diTahap('berjalan'))))))
            ->when($f['akan_dihapus'], fn ($q) => $q->akanDihapus())
            ->when($f['tag'], function ($q, $tags) {
                foreach ($tags as $id) {
                    $q->whereHas('extrasProfile.categories', fn ($c) => $c->where('extras_categories.id', $id));
                }
            })
            ->when($f['grade'] === 'belum', fn ($q) => $q->where('role', User::ROLE_EXTRAS)
                ->where(fn ($w) => $w->doesntHave('extrasProfile')->orWhereHas('extrasProfile', fn ($p) => $p->whereNull('grade_saat_ini'))))
            ->when(in_array($f['grade'], ['A', 'B', 'C'], true), fn ($q) => $q
                ->whereHas('extrasProfile', fn ($p) => $p->where('grade_saat_ini', $f['grade'])))
            ->with(['extrasProfile.categories', 'aktivitasTerakhir'])
            ->latest()
            ->paginate($f['role'] === User::ROLE_EXTRAS ? PerHalaman::dari($request, 24, PerHalaman::KARTU) : PerHalaman::dari($request, 25, PerHalaman::TABEL))
            ->withQueryString();

        $tagGroups = ExtrasCategory::perGrup();

        return view('super-admin.akun.index', compact('users', 'f', 'tagGroups'));
    }

    /** BD.4: halaman lama (Monitoring Akun, Kelola Akun) diarahkan ke Manajemen Akun. */
    public function keAkun(Request $request): RedirectResponse
    {
        $role = $request->query('role', $request->query('type'));
        $status = $request->query('status');

        return redirect()->route('super-admin.akun.index', array_filter([
            'q' => $request->query('search') ?: $request->query('q'),
            'role' => $role === 'all' ? null : $role,
            'status' => $status === 'all' ? null : $status,
        ]));
    }

    /**
     * Bagian AG/AR.2/AU.6.7: halaman detail per-akun Admin/Client/Extras dengan riwayat kerja lengkap & aktivitas.
     */
    public function show(User $user)
    {
        // Cegah Super Admin melihat dirinya sendiri atau Super Admin protected lainnya
        abort_if($user->is_protected && $user->id !== auth()->id(), 403);
        abort_if($user->id === auth()->id(), 403);

        $clientProjects = null;
        $availableKategori = null;

        // Load riwayat berdasarkan role
        if ($user->isClient()) {
            $assignments = collect();
            $clientProjects = CastingProject::query()->milikClient($user)->orderByDesc('id')->get();
        } elseif ($user->role === 'extras') {
            $user->load('extrasProfile.categories', 'extrasProfile.applications.castingProject');
            $assignments = collect();
            $availableKategori = ExtrasCategory::perGrup();
        } else {
            $user->load('adminProjectAssignments.castingProject', 'adminProjectAssignments.payroll.addons');
            $assignments = $user->adminProjectAssignments;
        }

        // AU.6.7: Aktivitas akun ini
        $userActivities = ActivityLog::where('user_id', $user->id)
            ->orWhere(fn ($q) => $q->where('subject_type', User::class)->where('subject_id', $user->id))
            ->latest()
            ->take(20)
            ->get();

        $notifikasi = $user->notifications()->take(20)->get();

        return view('super-admin.admins.show', compact('user', 'assignments', 'clientProjects', 'availableKategori', 'userActivities', 'notifikasi'));
    }

    /**
     * AU.6: Super Admin update data akun (nama, email, role).
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $this->guardTarget($user);

        $allowedRoles = ['admin', 'korlap', 'client', 'extras'];
        if ($request->user()->is_protected) {
            $allowedRoles[] = 'super_admin';
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'required_unless:role,client', 'email', 'unique:users,email,'.$user->id],
            'role' => ['required', 'in:'.implode(',', $allowedRoles)],
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
        ]);

        if ($user->role === 'extras') {
            ExtrasProfile::firstOrCreate(['user_id' => $user->id]);
        }

        ActivityLog::record('UPDATE_USER', "Super Admin mengubah data akun {$user->name} (Role: {$user->role}).", $user);

        return redirect()->back(fallback: route('super-admin.akun.index'))->with('status', "Data akun {$user->name} berhasil diperbarui.");
    }

    /**
     * BD.1: Super Admin bikin akun Client. Password sementara digenerate,
     * tampil sekali lewat flash `kredensial`, Client wajib ganti saat login.
     */
    public function storeClient(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nama_perusahaan' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'username' => ['required', 'alpha_dash', 'max:50', 'unique:users,username'],
            'nomor_wa' => ['nullable', 'string', 'max:20', new NomorWa],
        ]);

        $password = self::passwordSementara();
        $user = User::create($data + [
            'password' => $password,
            'wajib_ganti_password' => true,
            'role' => User::ROLE_CLIENT,
            'status' => 'aktif',
        ]);

        ActivityLog::record('CREATE_USER', "Super Admin membuat akun Client {$user->name} (@{$user->username}).", $user);

        return back()->with('status', "Akun Client {$user->name} berhasil dibuat.")
            ->with('kredensial', $this->kredensial($user, $password))
            ->with('client_baru_id', $user->id);
    }

    /**
     * RF-40: Super Admin menambahkan akun Admin baru + sub-role spesifik.
     */
    public function store(Request $request): RedirectResponse
    {
        $allowedRoles = ['admin', 'korlap'];
        if ($request->user()->is_protected) {
            $allowedRoles[] = 'super_admin';
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8'],
            'role' => ['required', 'in:'.implode(',', $allowedRoles)],
            'honor_nominal' => ['nullable', 'numeric', 'min:0'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'status' => 'aktif',
            'honor_nominal' => $data['role'] === 'super_admin' ? null : ($data['honor_nominal'] ?? null),
        ]);

        return redirect()->route('super-admin.akun.index')->with('status', 'Akun Admin berhasil ditambahkan.');
    }

    /**
     * RF-41: Super Admin adjust nominal honor kapan saja setelah rekrut.
     */
    public function updateHonor(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'honor_nominal' => ['required', 'numeric', 'min:0'],
        ]);

        abort_unless(in_array($user->role, [User::ROLE_ADMIN, User::ROLE_KORLAP], true), 404);

        $lama = $user->honor_nominal;
        $user->update(['honor_nominal' => $data['honor_nominal']]);
        ActivityLog::record('UPDATE_HONOR', "Honor {$user->name}: Rp ".number_format((float) $lama, 0, ',', '.').' → Rp '.number_format((float) $data['honor_nominal'], 0, ',', '.'), $user, ['lama' => $lama, 'baru' => $data['honor_nominal']]);

        return back()->with('status', 'Nominal honor diperbarui.');
    }

    /**
     * RF-57: nonaktifkan/aktifkan akun Admin/Client/Super Admin lain.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        $this->guardTarget($user);

        $user->status = $user->status === 'aktif' ? 'nonaktif' : 'aktif';
        $user->save();

        ActivityLog::record('TOGGLE_USER_STATUS', "Super Admin mengubah status {$user->name} ke {$user->status}.", $user);

        return back()->with('status', "Status akun {$user->name} diperbarui.");
    }

    /**
     * RF-57: Nonaktifkan / Soft-Delete akun. Tidak ada hard delete.
     * Histori dan data akun tetap tersimpan aman di database.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->guardTarget($user);

        $user->status = 'nonaktif';
        $user->save();
        $user->delete();

        return back()->with('status', 'Akun berhasil dinonaktifkan/diarsipkan. Data histori tetap tersimpan aman.');
    }

    /**
     * Mengembalikan akun yang sebelumnya dinonaktifkan / di-soft delete.
     */
    public function restore(int $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);
        $this->guardTarget($user);

        $user->restore();
        $user->status = 'aktif';
        $user->save();

        return back()->with('status', 'Akun berhasil diaktifkan kembali.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['exists:users,id'],
            'action' => ['required', 'in:aktifkan,nonaktifkan'],
        ]);

        $users = User::whereIn('id', $data['user_ids'])
            ->where('id', '!=', auth()->id())
            ->get()
            ->filter(fn ($u) => ! $u->is_protected);

        $status = $data['action'] === 'aktifkan' ? 'aktif' : 'nonaktif';

        foreach ($users as $u) {
            $u->status = $status;
            $u->save();
            ActivityLog::record('TOGGLE_USER_STATUS', "Bulk action: status {$u->name} diubah ke {$status}.", $u);
        }

        return back()->with('status', "{$users->count()} akun berhasil di{$data['action']}.");
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $this->guardTarget($user);

        $newPassword = self::passwordSementara();
        $user->update(['password' => $newPassword, 'wajib_ganti_password' => true]);

        ActivityLog::record('RESET_USER_PASSWORD', "Super Admin mereset password {$user->name}.", $user);

        return back()->with('status', "Password {$user->name} berhasil direset.")
            ->with('kredensial', $this->kredensial($user, $newPassword));
    }

    public static function passwordSementara(int $panjang = 10): string
    {
        $huruf = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        return collect(range(1, $panjang))->map(fn () => $huruf[random_int(0, strlen($huruf) - 1)])->implode('');
    }

    private function kredensial(User $user, string $password): array
    {
        return [
            'nama' => $user->name,
            'username' => $user->username ?: $user->email,
            'password' => $password,
            'url' => route('login'),
            'nomor_wa' => $user->nomor_wa,
        ];
    }

    private function guardTarget(User $user): void
    {
        abort_if($user->is_protected || $user->id === auth()->id(), 403);
    }
}
