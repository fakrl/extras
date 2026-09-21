<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AdminProfile;
use App\Models\CastingProject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminManagementController extends Controller
{
    /**
     * RF-57: target Admin (4 sub-role) + sesama Super Admin + Casting Director.
     * Semua ditampilkan dalam 1 list dengan filter/tab role. User yang sedang login
     * dikecualikan (tidak boleh aksi ke dirinya sendiri).
     *
     * Bagian AG: Default listing (role=all) HANYA tampilkan Admin roles, bukan CD.
     * CD hanya muncul kalau explicit filter role=casting_director.
     */
    public function index(Request $request)
    {
        $roleFilter = $request->query('role', 'all');
        $statusFilter = $request->query('status', 'all');

        $query = User::withTrashed()->where('id', '!=', auth()->id());

        if ($roleFilter === 'all') {
            // Default: hanya Admin roles (tidak termasuk CD/Client)
            $query->whereIn('role', ['admin', 'korlap', 'super_admin', 'admin_default', 'admin_talco', 'admin_korlap', 'admin_sosmed']);
        } elseif ($roleFilter === 'client' || $roleFilter === 'casting_director') {
            $query->whereIn('role', ['client', 'casting_director']);
        } else {
            // Support aliases
            $targetRole = match ($roleFilter) {
                'admin' => ['admin', 'admin_default'],
                'korlap' => ['korlap', 'admin_korlap'],
                default => [$roleFilter],
            };
            $query->whereIn('role', $targetRole);
        }

        if ($statusFilter === 'aktif') {
            $query->whereNull('deleted_at')->where('status', 'aktif');
        } elseif ($statusFilter === 'nonaktif') {
            $query->where(function ($q) {
                $q->whereNotNull('deleted_at')->orWhere('status', 'nonaktif');
            });
        }

        $admins = $query->with([
            'adminProfile',
            'adminProjectAssignments.castingProject',
            'adminProjectAssignments.payroll',
            'cdProjectAssignments.castingProject',
        ])
            ->get()
            ->each(fn (User $admin) => $admin->has_history = $this->hasHistory($admin));

        $projects = CastingProject::orderByDesc('id')->get();

        return view('super-admin.admins.index', compact('admins', 'projects', 'roleFilter', 'statusFilter'));
    }

    /**
     * Bagian AG: halaman detail per-akun Admin/CD dengan riwayat kerja lengkap.
     */
    public function show(User $user)
    {
        // Cegah Super Admin melihat dirinya sendiri atau Super Admin protected lainnya
        abort_if($user->is_protected && $user->id !== auth()->id(), 403);
        abort_if($user->id === auth()->id(), 403);

        // Load riwayat berdasarkan role
        if ($user->isClient()) {
            $user->load('cdProjectAssignments.castingProject', 'cdProjectAssignments.cdReviews');
            $assignments = $user->cdProjectAssignments;
        } else {
            $user->load('adminProjectAssignments.castingProject', 'adminProjectAssignments.payroll.addons', 'adminProfile');
            $assignments = $user->adminProjectAssignments;
        }

        return view('super-admin.admins.show', compact('user', 'assignments'));
    }

    /**
     * RF-57: halaman terpisah untuk kelola Casting Director / Client.
     */
    public function indexCd()
    {
        $cds = User::whereIn('role', ['client', 'casting_director'])
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
        $allowedRoles = ['admin', 'korlap', 'admin_default', 'admin_talco', 'admin_korlap', 'admin_sosmed'];
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

        return back()->with('status', 'Status akun diperbarui.');
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
