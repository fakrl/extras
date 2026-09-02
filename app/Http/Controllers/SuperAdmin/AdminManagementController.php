<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AdminProfile;
use App\Models\CastingProject;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminManagementController extends Controller
{
    /**
     * RF-57: target Admin (4 sub-role) + sesama Super Admin. Casting Director
     * dikelola terpisah lewat indexCd(). User yang sedang login dikecualikan
     * (tidak boleh aksi ke dirinya sendiri, jadi tidak perlu muncul di daftar).
     */
    public function index()
    {
        $admins = User::whereIn('role', ['admin_default', 'admin_talco', 'admin_korlap', 'admin_sosmed', 'super_admin'])
            ->where('id', '!=', auth()->id())
            ->with('adminProfile', 'adminProjectAssignments.castingProject', 'adminProjectAssignments.payroll')
            ->get()
            ->each(fn (User $admin) => $admin->has_history = $this->hasHistory($admin));

        $projects = CastingProject::orderByDesc('id')->get();

        return view('super-admin.admins.index', compact('admins', 'projects'));
    }

    /**
     * RF-57: halaman terpisah untuk kelola Casting Director. Reuse
     * toggleStatus()/destroy()/hasHistory() yang sama dengan halaman Admin.
     */
    public function indexCd()
    {
        $cds = User::where('role', 'casting_director')
            ->where('id', '!=', auth()->id())
            ->get()
            ->each(fn (User $cd) => $cd->has_history = $this->hasHistory($cd));

        return view('super-admin.casting-directors.index', compact('cds'));
    }

    /**
     * RF-58 lanjutan: Super Admin bikin akun CD langsung, terpisah dari
     * alur self-register publik (register.cd). Tidak ada AdminProfile/honor
     * — konsep itu cuma buat 4 sub-role Admin.
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
            'role' => 'casting_director',
            'status' => 'aktif',
        ]);

        return redirect()->route('super-admin.casting-directors.index')->with('status', 'Akun Casting Director berhasil ditambahkan.');
    }

    /**
     * RF-40: Super Admin menambahkan akun Admin baru + sub-role spesifik.
     * RF-41: sekaligus menetapkan nominal honor per-event (nullable untuk
     * admin_default, karena dia bukan staf event-based).
     */
    public function store(Request $request): RedirectResponse
    {
        $allowedRoles = ['admin_default', 'admin_talco', 'admin_korlap', 'admin_sosmed'];
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
     * RF-57: hapus permanen. Guard dasar sama dengan toggleStatus, ditambah
     * cek histori lewat FK constraint DB (bukan enumerasi manual tiap tabel
     * yang belum tentu lengkap) — kalau DB tolak karena FK, akun punya
     * riwayat, arahkan ke nonaktifkan saja.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->guardTarget($user);

        try {
            DB::transaction(fn () => $user->delete());
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->with('error', 'Akun ini punya riwayat penugasan, nonaktifkan saja.');
            }

            throw $e;
        }

        $redirectRoute = $user->role === 'casting_director' ? 'super-admin.casting-directors.index' : 'super-admin.admins.index';

        return redirect()->route($redirectRoute)->with('status', 'Akun berhasil dihapus.');
    }

    private function guardTarget(User $user): void
    {
        abort_if($user->is_protected || $user->id === auth()->id(), 403);
    }

    /**
     * Flag ringan untuk UI (disable tombol hapus + tooltip alasan). Trial-delete
     * dibungkus transaction yang selalu rollback (probe exception), dengan
     * catch FK (code 23000) SAMA PERSIS dengan destroy() — jadi kedua fungsi
     * structurally tidak mungkin drift, bukan cuma re-sync manual per relasi.
     * Ambil instance baru (bukan $user yang dipakai ->each() di index()) supaya
     * atribut `exists` milik instance pemanggil tidak ikut ke-set false oleh
     * delete(), meski row DB-nya sendiri sudah pasti balik oleh rollback.
     */
    private function hasHistory(User $user): bool
    {
        try {
            DB::transaction(function () use ($user) {
                User::findOrFail($user->id)->delete();

                throw new \RuntimeException('rollback-probe');
            });
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return true;
            }

            throw $e;
        } catch (\RuntimeException) {
            return false;
        }

        return false;
    }
}
