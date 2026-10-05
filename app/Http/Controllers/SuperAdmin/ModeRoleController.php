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
        if (! in_array($mode, ViewAs::MODES_LIHAT, true)) {
            return redirect()->route('super-admin.monitoring.'.$mode);
        }

        $q = trim((string) $request->query('q'));
        $akun = User::where('role', $mode)->where('status', 'aktif')->where('is_protected', false)
            ->when($q !== '', fn ($w) => $w->where(fn ($w) => $w->where('name', 'like', "%{$q}%")
                ->orWhere('username', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->orWhere('nama_perusahaan', 'like', "%{$q}%")))
            ->orderBy('name')->paginate(PerHalaman::dari($request, PerHalaman::TABEL))->withQueryString();

        return view('super-admin.mode-role', compact('mode', 'akun', 'q'));
    }

    public function mulai(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(ViewAs::MODES)],
            'user_id' => ['nullable', 'integer'],
            'ke' => ['nullable', 'string', 'max:500'],
            'kembali' => ['nullable', 'string', 'max:500'],
        ]);

        $sa = $request->user();
        $target = null;
        if (in_array($data['mode'], ViewAs::MODES_LIHAT, true)) {
            $target = User::find($data['user_id'] ?? 0);
            abort_unless(ViewAs::bolehDilihat($target, $data['mode']), 403, 'Akun ini tidak bisa dilihat lewat mode Monitoring.');
        }

        $request->session()->forget(['sa_mode', 'sa_view_user_id', 'sa_kembali']);
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
        if ($kembali = self::internal($data['kembali'] ?? null, '/super-admin/monitoring/')) {
            $request->session()->put('sa_kembali', $kembali);
        }

        return redirect($target ? $target->dashboardUrl() : (self::internal($data['ke'] ?? null, '/admin/') ?? $sa->dashboardUrl()));
    }

    public function keluar(Request $request): RedirectResponse
    {
        $mode = $request->session()->get('sa_mode');
        $kembali = self::internal($request->session()->pull('sa_kembali'), '/super-admin/monitoring/');
        $request->session()->forget(['sa_mode', 'sa_view_user_id']);

        if ($mode) {
            ActivityLog::record('SA_MODE_KELUAR', "Super Admin {$request->user()->name} keluar dari Monitoring sebagai ".(User::LABELS[$mode] ?? $mode), null, ['mode' => $mode]);
        }

        return $kembali ? redirect($kembali) : redirect()->route('super-admin.dashboard');
    }

    // BL.3: tujuan redirect cuma path internal relatif berawalan $awalan (cegah open redirect).
    private static function internal(?string $url, string $awalan): ?string
    {
        return $url && str_starts_with($url, $awalan) && ! preg_match('/[\\\\\s]/', $url) ? $url : null;
    }
}
