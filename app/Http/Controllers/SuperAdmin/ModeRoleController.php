<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ViewAs;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\PerHalaman;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// SPEC BD.6: Monitoring - Super Admin sebagai Admin/Korlap (aksi) atau Client/Extras (lihat saja).
class ModeRoleController extends Controller
{
    public function pilih(Request $request, string $mode)
    {
        abort_unless(in_array($mode, ViewAs::MODES, true), 404);

        $q = trim((string) $request->query('q'));
        $akun = in_array($mode, ViewAs::MODES_LIHAT, true)
            ? User::where('role', $mode)->where('status', 'aktif')->where('is_protected', false)
                ->when($q !== '', fn ($w) => $w->where(fn ($w) => $w->where('name', 'like', "%{$q}%")
                    ->orWhere('username', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('nama_perusahaan', 'like', "%{$q}%")))
                ->orderBy('name')->paginate(PerHalaman::dari($request, 25, PerHalaman::TABEL))->withQueryString()
            : null;

        return view('super-admin.mode-role', compact('mode', 'akun', 'q'));
    }

    public function mulai(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(ViewAs::MODES)],
            'user_id' => ['nullable', 'integer'],
        ]);

        $sa = $request->user();
        $target = null;
        if (in_array($data['mode'], ViewAs::MODES_LIHAT, true)) {
            $target = User::find($data['user_id'] ?? 0);
            abort_unless(ViewAs::bolehDilihat($target, $data['mode']), 403, 'Akun ini tidak bisa dilihat lewat mode Monitoring.');
        }

        $request->session()->forget(['sa_mode', 'sa_view_user_id']);
        ActivityLog::record(
            'SA_MODE_MULAI',
            "Super Admin {$sa->name} mulai Monitoring sebagai ".User::LABELS[$data['mode']].($target ? " ({$target->name})" : ''),
            $target,
            ['mode' => $data['mode']]
        );

        $request->session()->put('sa_mode', $data['mode']);
        if ($target) {
            $request->session()->put('sa_view_user_id', $target->id);
        }

        return redirect($target ? $target->dashboardUrl() : $sa->dashboardUrl());
    }

    public function keluar(Request $request): RedirectResponse
    {
        $mode = $request->session()->get('sa_mode');
        $request->session()->forget(['sa_mode', 'sa_view_user_id']);

        if ($mode) {
            ActivityLog::record('SA_MODE_KELUAR', "Super Admin {$request->user()->name} keluar dari Monitoring sebagai ".(User::LABELS[$mode] ?? $mode), null, ['mode' => $mode]);
        }

        return redirect()->route('super-admin.dashboard');
    }
}
