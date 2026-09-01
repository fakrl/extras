<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AdminProfile;
use App\Models\AdminProjectAssignment;
use App\Models\Attendance;
use App\Models\CastingProject;
use App\Models\CdProjectAssignment;
use App\Models\CdReview;
use App\Models\FieldNote;
use App\Models\NotificationLog;
use App\Models\PaymentAddon;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminManagementController extends Controller
{
    /**
     * RF-57: target diperluas dari Admin (4 sub-role) → + Casting Director +
     * sesama Super Admin. User yang sedang login dikecualikan (tidak boleh
     * aksi ke dirinya sendiri, jadi tidak perlu muncul di daftar).
     */
    public function index()
    {
        $admins = User::whereIn('role', ['admin_default', 'admin_talco', 'admin_korlap', 'admin_sosmed', 'casting_director', 'super_admin'])
            ->where('id', '!=', auth()->id())
            ->with('adminProfile', 'adminProjectAssignments.castingProject', 'adminProjectAssignments.payroll')
            ->get()
            ->each(fn (User $admin) => $admin->has_history = $this->hasHistory($admin));

        $projects = CastingProject::orderByDesc('id')->get();

        return view('super-admin.admins.index', compact('admins', 'projects'));
    }

    public function create()
    {
        return view('super-admin.admins.create');
    }

    /**
     * RF-40: Super Admin menambahkan akun Admin baru + sub-role spesifik.
     * RF-41: sekaligus menetapkan nominal honor per-event (nullable untuk
     * admin_default, karena dia bukan staf event-based).
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8'],
            'role' => ['required', 'in:admin_default,admin_talco,admin_korlap,admin_sosmed'],
            'honor_nominal' => ['nullable', 'numeric', 'min:0'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'status' => 'aktif',
        ]);

        AdminProfile::create([
            'user_id' => $user->id,
            'honor_nominal' => $data['honor_nominal'] ?? null,
            'created_by' => $request->user()->id,
        ]);

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

        return redirect()->route('super-admin.admins.index')->with('status', 'Akun berhasil dihapus.');
    }

    private function guardTarget(User $user): void
    {
        abort_if($user->is_protected || $user->id === auth()->id(), 403);
    }

    /**
     * Flag ringan untuk UI (disable tombol hapus + tooltip alasan) — bukan
     * satu-satunya penjaga, destroy() tetap divalidasi ulang via FK constraint.
     */
    private function hasHistory(User $user): bool
    {
        return $user->castingProjects()->exists()
            || $user->adminProjectAssignments()->exists()
            || AdminProjectAssignment::where('assigned_by', $user->id)->exists()
            || CdReview::where('cd_id', $user->id)->exists()
            || PaymentAddon::where('created_by', $user->id)->exists()
            || FieldNote::where('korlap_id', $user->id)->exists()
            || Attendance::where('dicatat_oleh', $user->id)->exists()
            || CdProjectAssignment::where('cd_user_id', $user->id)->exists()
            || NotificationLog::where('user_id', $user->id)->exists();
    }
}
