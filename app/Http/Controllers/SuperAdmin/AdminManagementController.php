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
    public function index()
    {
        $admins = User::whereIn('role', ['admin_default', 'admin_talco', 'admin_korlap', 'admin_sosmed'])
            ->with('adminProfile', 'adminProjectAssignments.castingProject', 'adminProjectAssignments.payroll')
            ->get();

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
}
