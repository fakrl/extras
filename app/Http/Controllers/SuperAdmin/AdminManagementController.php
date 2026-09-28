<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AdminProfile;
use App\Models\CastingProject;
use App\Models\ExtrasCategory;
use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminManagementController extends Controller
{
    /**
     * RF-57: target Admin (4 sub-role) + sesama Super Admin + Casting Director.
     * Semua ditampilkan dalam 1 list dengan filter/tab role. User yang sedang login
     * dikecualikan (tidak boleh aksi ke dirinya sendiri).
     *
     * Bagian AG: Default listing (role=all) HANYA tampilkan Admin roles, bukan CD.
     * CD hanya muncul kalau explicit filter role=client.
     */
    public function index(Request $request)
    {
        $roleFilter = $request->query('role', 'all');
        $statusFilter = $request->query('status', 'all');
        $search = $request->query('search') ?: $request->query('q');

        $query = User::withTrashed()->where('id', '!=', auth()->id());

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%");
            });
        }

        if ($roleFilter === 'all') {
            // Default: hanya Admin roles (tidak termasuk CD/Client/Extras)
            $query->whereIn('role', ['admin', 'korlap', 'super_admin']);
        } else {
            $query->where('role', $roleFilter);
        }

        if ($statusFilter === 'aktif') {
            $query->whereNull('deleted_at')->where('status', 'aktif');
        } elseif ($statusFilter === 'nonaktif') {
            $query->where(function ($q) {
                $q->whereNotNull('deleted_at')->orWhere('status', 'nonaktif');
            });
        }

        $withs = $roleFilter === 'extras'
            ? []
            : ['adminProfile', 'adminProjectAssignments.castingProject', 'adminProjectAssignments.payroll', 'cdProjectAssignments.castingProject'];

        $admins = $query->with($withs)
            ->latest()
            ->paginate(15)
            ->appends($request->query());

        $admins->each(fn (User $admin) => $admin->has_history = $this->hasHistory($admin));

        $projects = CastingProject::orderByDesc('id')->get();

        return view('super-admin.admins.index', compact('admins', 'projects', 'roleFilter', 'statusFilter', 'search'));
    }

    /**
     * Bagian AG/AR.2/AU.6.7: halaman detail per-akun Admin/CD/Extras dengan riwayat kerja lengkap & aktivitas.
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
            $user->load('cdProjectAssignments.castingProject', 'cdProjectAssignments.cdReviews');
            $assignments = $user->cdProjectAssignments;
            $clientProjects = CastingProject::where('diajukan_oleh_client_id', $user->id)
                ->orderByDesc('id')->get();
        } elseif ($user->role === 'extras') {
            $user->load('extrasProfile.categories', 'adminProjectAssignments');
            $assignments = collect();
            $availableKategori = ExtrasCategory::orderBy('nama')->get();
        } else {
            $user->load('adminProjectAssignments.castingProject', 'adminProjectAssignments.payroll.addons', 'adminProfile');
            $assignments = $user->adminProjectAssignments;
        }

        // AU.6.7: Aktivitas akun ini
        $userActivities = ActivityLog::where('user_id', $user->id)
            ->orWhere(fn ($q) => $q->where('subject_type', User::class)->where('subject_id', $user->id))
            ->latest()
            ->take(20)
            ->get();

        return view('super-admin.admins.show', compact('user', 'assignments', 'clientProjects', 'availableKategori', 'userActivities'));
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
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
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

        return redirect()->back(fallback: route('super-admin.admins.index'))->with('status', "Data akun {$user->name} berhasil diperbarui.");
    }

    /**
     * RF-57: halaman terpisah untuk kelola Casting Director / Client.
     */
    public function indexCd()
    {
        $cds = User::where('role', 'client')
            ->where('id', '!=', auth()->id())
            ->get()
            ->each(fn (User $cd) => $cd->has_history = $this->hasHistory($cd));

        return view('super-admin.casting-directors.index', compact('cds'));
    }

    /**
     * RF-58 lanjutan: Super Admin bikin akun Client langsung, terpisah dari
     * alur self-register publik (register.cd).
     */
    public function storeCd(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'client',
            'status' => 'aktif',
        ]);

        return redirect()->route('super-admin.admins.index', ['role' => 'client'])->with('status', 'Akun Client berhasil ditambahkan.');
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

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'status' => 'aktif',
        ]);

        if ($data['role'] !== 'super_admin') {
            AdminProfile::create([
                'user_id' => $user->id,
                'honor_nominal' => $data['honor_nominal'] ?? null,
                'created_by' => $request->user()->id,
            ]);
        }

        return redirect()->route('super-admin.admins.index')->with('status', 'Akun Admin berhasil ditambahkan.');
    }

    /**
     * RF-41: Super Admin adjust nominal honor kapan saja setelah rekrut.
     */
    public function updateHonor(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'honor_nominal' => ['required', 'numeric', 'min:0'],
        ]);

        abort_unless($user->adminProfile, 404);

        $user->adminProfile->updateHonor($data['honor_nominal']);

        return back()->with('status', 'Nominal honor diperbarui.');
    }

    /**
     * RF-57: nonaktifkan/aktifkan akun Admin/CD/Super Admin lain.
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
     * AR.2: Super Admin memperbarui kategori akun Extras.
     */
    public function updateKategori(User $user, Request $request): RedirectResponse
    {
        abort_unless($user->role === 'extras', 403);

        $data = $request->validate([
            'kategori_ids' => ['nullable', 'array'],
            'kategori_ids.*' => ['exists:extras_categories,id'],
        ]);

        $user->extrasProfile?->categories()->sync($data['kategori_ids'] ?? []);
        ActivityLog::record('UPDATE_EXTRAS_KATEGORI', "Super Admin memperbarui kategori {$user->name}.", $user);

        return back()->with('status', 'Kategori extras diperbarui.');
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

        $newPassword = Str::random(12);
        $user->password = Hash::make($newPassword);
        $user->save();

        ActivityLog::record('RESET_USER_PASSWORD', "Super Admin mereset password {$user->name}.", $user);

        return back()->with('status', "Password berhasil direset. Password baru: {$newPassword}");
    }

    private function guardTarget(User $user): void
    {
        abort_if($user->is_protected || $user->id === auth()->id(), 403);
    }

    /**
     * Flag untuk UI mengecek apakah pengguna memiliki riwayat kerja / penugasan.
     */
    private function hasHistory(User $user): bool
    {
        if ($user->adminProjectAssignments()->exists()) {
            return true;
        }
        if ($user->cdProjectAssignments()->exists()) {
            return true;
        }
        if ($user->castingProjects()->exists()) {
            return true;
        }

        return false;
    }
}
