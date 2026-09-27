<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $roleFilter = $request->query('role', 'all');
        $search = $request->query('q');
        $entityFilter = $request->query('entity', 'all');
        $period = $request->query('period', '7d');

        $startDate = match ($period) {
            '1d' => now()->startOfDay(),
            '30d' => now()->subDays(30)->startOfDay(),
            '90d' => now()->subDays(90)->startOfDay(),
            default => now()->subDays(7)->startOfDay(), // 7d
        };

        $query = ActivityLog::with('user')->latest('created_at')
            ->where('created_at', '>=', $startDate);

        if ($roleFilter !== 'all') {
            $query->where('role', $roleFilter);
        }

        if ($entityFilter !== 'all') {
            $query->where('subject_type', $entityFilter);
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

        // Distinct subject_type options (non-null only)
        $entityTypes = ActivityLog::whereNotNull('subject_type')
            ->distinct()
            ->pluck('subject_type')
            ->sort()
            ->values();

        // Counter per subject_type in period
        $entityCounts = ActivityLog::selectRaw('subject_type, count(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('subject_type')
            ->pluck('count', 'subject_type');

        // Trend chart data
        $chartData = $this->buildChartData($startDate, $period, $roleFilter, $entityFilter, $search);

        return view('super-admin.activity-logs.index', compact(
            'logs', 'roleFilter', 'search', 'roleCounts',
            'entityFilter', 'entityTypes', 'period',
            'entityCounts', 'chartData'
        ));
    }

    private function buildChartData(Carbon $startDate, string $period, string $roleFilter, string $entityFilter, ?string $search): array
    {
        $baseQuery = ActivityLog::query()->where('created_at', '>=', $startDate);

        if ($roleFilter !== 'all') {
            $baseQuery->where('role', $roleFilter);
        }
        if ($entityFilter !== 'all') {
            $baseQuery->where('subject_type', $entityFilter);
        }
        if ($search) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($period === '1d') {
            // Group per hour
            $rows = (clone $baseQuery)
                ->selectRaw('HOUR(created_at) as hour, count(*) as count')
                ->groupBy('hour')
                ->pluck('count', 'hour');

            $labels = [];
            $data = [];
            for ($h = 0; $h < 24; $h++) {
                $labels[] = str_pad($h, 2, '0', STR_PAD_LEFT).':00';
                $data[] = $rows[$h] ?? 0;
            }
        } else {
            $days = match ($period) {
                '30d' => 30,
                '90d' => 90,
                default => 7,
            };

            $rows = (clone $baseQuery)
                ->selectRaw('DATE(created_at) as day, count(*) as count')
                ->groupBy('day')
                ->pluck('count', 'day');

            $labels = [];
            $data = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i)->toDateString();
                $labels[] = Carbon::parse($date)->translatedFormat('j M');
                $data[] = $rows[$date] ?? 0;
            }
        }

        return compact('labels', 'data');
    }
}
