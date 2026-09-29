<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use App\Support\PerHalaman;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->validate([
            'q' => 'nullable|string|max:100',
            'aktor' => 'nullable|string|max:100',
            'role' => 'nullable|string|max:30',
            'aksi' => 'nullable|string|max:60',
            'dari' => 'nullable|date',
            'sampai' => 'nullable|date',
        ]);

        $logs = ActivityLog::with(['user', 'subject'])
            ->when($f['q'] ?? null, function ($query, $q) {
                $like = "%{$q}%";
                $query->where(fn ($w) => $w->where('description', 'like', $like)
                    ->orWhere('action', 'like', $like)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like)->orWhere('email', 'like', $like))
                    ->orWhereHasMorph('subject', [User::class, CastingProject::class, ExtrasProfile::class, ProjectApplication::class], fn ($s, $type) => match ($type) {
                        User::class => $s->where('name', 'like', $like),
                        CastingProject::class => $s->where('nama_produksi', 'like', $like),
                        ExtrasProfile::class => $s->whereHas('user', fn ($u) => $u->where('name', 'like', $like)),
                        ProjectApplication::class => $s->where(fn ($pa) => $pa->whereHas('castingProject', fn ($p) => $p->where('nama_produksi', 'like', $like))
                            ->orWhereHas('extras.user', fn ($u) => $u->where('name', 'like', $like))),
                    }));
            })
            ->when($f['aktor'] ?? null, fn ($query, $a) => $query->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$a}%")->orWhere('email', 'like', "%{$a}%")))
            ->when($f['role'] ?? null, fn ($query, $r) => $query->where('role', $r))
            ->when($f['aksi'] ?? null, fn ($query, $a) => $query->where('action', $a))
            ->when($f['dari'] ?? null, fn ($query, $d) => $query->where('created_at', '>=', $d))
            ->when($f['sampai'] ?? null, fn ($query, $d) => $query->where('created_at', '<', date('Y-m-d', strtotime($d.' +1 day'))))
            ->latest('created_at')
            ->paginate(PerHalaman::dari($request, 25, PerHalaman::TABEL))
            ->withQueryString();

        $aksiList = ActivityLog::distinct()->orderBy('action')->pluck('action');
        $aktorList = User::whereIn('id', ActivityLog::select('user_id')->whereNotNull('user_id'))->orderBy('name')->limit(200)->pluck('name');

        return view('super-admin.activity-logs.index', compact('logs', 'f', 'aksiList', 'aktorList'));
    }
}
