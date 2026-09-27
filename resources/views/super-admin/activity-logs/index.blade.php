@extends('layouts.app')

@section('title', 'Log Aktivitas Sistem (Audit Trail)')

@section('content')
<div style="margin-bottom: 20px;">
    <p style="color: var(--text-secondary); margin: -8px 0 16px; font-size: 13.5px;">
        Audit trail linimasa terpusat mencakup seluruh aktivitas dari 5 role resmi (Super Admin, Admin, Korlap, Client, dan Extras).
    </p>

    {{-- Filter Form --}}
    <form method="GET" action="{{ route('super-admin.activity-logs') }}" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-bottom: 16px;">
        <input type="text" name="q" value="{{ $search }}" class="input-inline" placeholder="Cari aksi, nama user, atau deskripsi..." style="flex: 1; min-width: 200px;">

        <select name="role" class="input-inline" style="width: auto;">
            <option value="all" {{ $roleFilter === 'all' ? 'selected' : '' }}>Semua Role</option>
            <option value="super_admin" {{ $roleFilter === 'super_admin' ? 'selected' : '' }}>Super Admin ({{ $roleCounts['super_admin'] ?? 0 }})</option>
            <option value="admin" {{ $roleFilter === 'admin' ? 'selected' : '' }}>Admin ({{ $roleCounts['admin'] ?? 0 }})</option>
            <option value="korlap" {{ $roleFilter === 'korlap' ? 'selected' : '' }}>Korlap ({{ $roleCounts['korlap'] ?? 0 }})</option>
            <option value="client" {{ $roleFilter === 'client' ? 'selected' : '' }}>Client ({{ $roleCounts['client'] ?? 0 }})</option>
            <option value="extras" {{ $roleFilter === 'extras' ? 'selected' : '' }}>Extras ({{ $roleCounts['extras'] ?? 0 }})</option>
        </select>

        <select name="entity" class="input-inline" style="width: auto;">
            <option value="all" {{ $entityFilter === 'all' ? 'selected' : '' }}>Semua Entitas</option>
            @foreach ($entityTypes as $et)
                <option value="{{ $et }}" {{ $entityFilter === $et ? 'selected' : '' }}>
                    {{ class_basename($et) }}
                </option>
            @endforeach
        </select>

        <select name="period" class="input-inline" style="width: auto;">
            <option value="1d" {{ $period === '1d' ? 'selected' : '' }}>Hari Ini</option>
            <option value="7d" {{ $period === '7d' ? 'selected' : '' }}>7 Hari</option>
            <option value="30d" {{ $period === '30d' ? 'selected' : '' }}>30 Hari</option>
            <option value="90d" {{ $period === '90d' ? 'selected' : '' }}>90 Hari</option>
        </select>

        <button type="submit" class="btn btn-sm btn-brand"><i class="ti ti-search"></i></button>
        @if ($search || $entityFilter !== 'all' || $roleFilter !== 'all')
            <a href="{{ route('super-admin.activity-logs', ['period' => $period]) }}" class="btn btn-sm">Reset</a>
        @endif
    </form>

    {{-- AT.3.2: Counter per Entitas --}}
    @if ($entityCounts->isNotEmpty())
    <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; overflow-x: auto; padding-bottom: 4px;">
        @php
            $nullCount = $entityCounts[null] ?? $entityCounts->get('') ?? 0;
            $otherCount = $entityCounts->filter(fn ($v, $k) => !empty($k))->sum();
        @endphp
        @foreach ($entityCounts as $type => $count)
            @if (!empty($type))
            <div style="display: flex; align-items: center; gap: 6px; background: var(--bg-card); border: 1px solid var(--border); border-radius: 8px; padding: 6px 12px; white-space: nowrap; font-size: 12.5px;">
                <span style="font-weight: 600; color: var(--accent);">{{ class_basename($type) }}</span>
                <span style="color: var(--text-muted);">:</span>
                <span style="font-weight: 700; color: var(--text-primary);">{{ $count }}</span>
            </div>
            @endif
        @endforeach
        @if ($nullCount > 0)
        <div style="display: flex; align-items: center; gap: 6px; background: var(--bg-card); border: 1px solid var(--border); border-radius: 8px; padding: 6px 12px; white-space: nowrap; font-size: 12.5px;">
            <span style="font-weight: 600; color: var(--text-secondary);">Lainnya</span>
            <span style="color: var(--text-muted);">:</span>
            <span style="font-weight: 700; color: var(--text-primary);">{{ $nullCount }}</span>
        </div>
        @endif
    </div>
    @endif

    {{-- AT.3.3: Trend Chart --}}
    <div class="card" style="margin-bottom: 16px; padding: 16px;">
        <div style="font-size: 13px; font-weight: 600; color: var(--text-secondary); margin-bottom: 10px;">
            Tren Aktivitas &mdash;
            @if($period === '1d') Hari ini (per jam)
            @elseif($period === '30d') 30 Hari Terakhir
            @elseif($period === '90d') 90 Hari Terakhir
            @else 7 Hari Terakhir
            @endif
        </div>
        <div style="height: 160px; position: relative;">
            <canvas id="chartActivityTrend"></canvas>
        </div>
    </div>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
        <div style="font-size: 14.5px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
            <i class="ti ti-activity" style="color: var(--accent);"></i> Riwayat Log Aktivitas
        </div>
        <div style="font-size: 12.5px; color: var(--text-muted);">
            Menampilkan {{ $logs->firstItem() ?? 0 }} - {{ $logs->lastItem() ?? 0 }} dari total {{ $logs->total() }} aktivitas
        </div>
    </div>

    <div style="overflow-x: auto;">
        <div class="table-container">
        <table style="width: 100%;">
            <thead>
                <tr>
                    <th style="white-space: nowrap;">Waktu</th>
                    <th>Aktor</th>
                    <th>Role</th>
                    <th>Kode Aksi</th>
                    <th>Deskripsi Aktivitas</th>
                    <th style="text-align: right;">IP Address</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    @php
                        $roleBadgeClass = match($log->role) {
                            'super_admin' => 'badge-pending',
                            'admin' => 'badge-aktif',
                            'korlap' => 'badge-pending',
                            'client' => 'badge-aktif',
                            'extras' => 'badge-pending',
                            default => 'badge-pending',
                        };
                    @endphp
                    <tr>
                        <td style="white-space: nowrap; font-size: 12.5px; color: var(--text-secondary);">
                            {{ $log->created_at->format('d/m/Y H:i:s') }}
                        </td>
                        <td>
                            @if ($log->user)
                                <span style="font-weight: 600;">{{ $log->user->name }}</span>
                                <div style="font-size: 11.5px; color: var(--text-muted);">{{ $log->user->email }}</div>
                            @else
                                <span style="color: var(--text-muted); font-style: italic;">Sistem / Anonim</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $roleBadgeClass }}">
                                {{ $log->role }}
                            </span>
                        </td>
                        <td>
                            <code style="font-size: 11.5px; background: var(--bg-nav-active); padding: 3px 6px; border-radius: 4px; color: var(--accent-strong);">
                                {{ $log->action }}
                            </code>
                        </td>
                        <td style="font-size: 13px;">
                            {{ $log->description }}
                            @if (!empty($log->properties))
                                <details style="margin-top: 4px; font-size: 11.5px; color: var(--text-muted);">
                                    <summary style="cursor: pointer; color: var(--accent);">Lihat Parameter</summary>
                                    <pre style="background: var(--bg-page); padding: 8px; border-radius: 6px; margin-top: 4px; overflow-x: auto;">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </details>
                            @endif
                        </td>
                        <td style="text-align: right; font-size: 12px; color: var(--text-muted); white-space: nowrap;">
                            {{ $log->ip_address ?? '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px 0;">
                            Belum ada catatan aktivitas yang sesuai filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div style="margin-top: 16px;">
        {{ $logs->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    var gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
    var textColor = isDark ? '#9db3a2' : '#435449';

    new Chart(document.getElementById('chartActivityTrend'), {
        type: 'bar',
        data: {
            labels: @json($chartData['labels']),
            datasets: [{
                data: @json($chartData['data']),
                backgroundColor: 'var(--accent)',
                borderRadius: 5,
                maxBarThickness: 36,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: textColor, maxRotation: 30, minRotation: 30 }, grid: { display: false } },
                y: { ticks: { color: textColor, precision: 0 }, grid: { color: gridColor }, beginAtZero: true }
            }
        }
    });
})();
</script>
@endpush
