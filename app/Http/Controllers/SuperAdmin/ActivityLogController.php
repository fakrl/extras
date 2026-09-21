<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Tampilkan riwayat audit log seluruh aktivitas aktor (5 Role).
     */
    public function index(Request $request)
    {
        $roleFilter = $request->query('role', 'all');
        $search = $request->query('q');

        $query = ActivityLog::with('user')->latest('created_at');

        if ($roleFilter !== 'all') {
            $query->where('role', $roleFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $logs = $query->paginate(25)->withQueryString();

        $roleCounts = ActivityLog::selectRaw('role, count(*) as count')
            ->groupBy('role')
            ->pluck('count', 'role');

        return view('super-admin.activity-logs.index', compact('logs', 'roleFilter', 'search', 'roleCounts'));
    }
}
