@extends('layouts.app')

@section('title', 'Log Aktivitas Sistem (Audit Trail)')

@section('content')
<div style="margin-bottom: 20px;">
    <p style="color: var(--text-secondary); margin: -8px 0 16px; font-size: 13.5px;">
        Audit trail linimasa terpusat mencakup seluruh aktivitas dari 5 role resmi (Super Admin, Admin, Korlap, Client, dan Extras).
    </p>

    {{-- Filter Role Tabs --}}
    <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-bottom: 16px;">
        <a href="{{ route('super-admin.activity-logs', ['role' => 'all', 'q' => $search]) }}"
           class="btn btn-sm {{ $roleFilter === 'all' ? 'btn-brand' : '' }}" style="font-size: 12.5px;">
            Semua Role
        </a>
        <a href="{{ route('super-admin.activity-logs', ['role' => 'super_admin', 'q' => $search]) }}"
           class="btn btn-sm {{ $roleFilter === 'super_admin' ? 'btn-brand' : '' }}" style="font-size: 12.5px;">
            Super Admin ({{ $roleCounts['super_admin'] ?? 0 }})
        </a>
        <a href="{{ route('super-admin.activity-logs', ['role' => 'admin', 'q' => $search]) }}"
           class="btn btn-sm {{ $roleFilter === 'admin' ? 'btn-brand' : '' }}" style="font-size: 12.5px;">
            Admin ({{ $roleCounts['admin'] ?? 0 }})
        </a>
        <a href="{{ route('super-admin.activity-logs', ['role' => 'korlap', 'q' => $search]) }}"
           class="btn btn-sm {{ $roleFilter === 'korlap' ? 'btn-brand' : '' }}" style="font-size: 12.5px;">
            Korlap ({{ $roleCounts['korlap'] ?? 0 }})
        </a>
        <a href="{{ route('super-admin.activity-logs', ['role' => 'client', 'q' => $search]) }}"
           class="btn btn-sm {{ $roleFilter === 'client' ? 'btn-brand' : '' }}" style="font-size: 12.5px;">
            Client / PH ({{ $roleCounts['client'] ?? 0 }})
        </a>
        <a href="{{ route('super-admin.activity-logs', ['role' => 'extras', 'q' => $search]) }}"
           class="btn btn-sm {{ $roleFilter === 'extras' ? 'btn-brand' : '' }}" style="font-size: 12.5px;">
            Extras ({{ $roleCounts['extras'] ?? 0 }})
        </a>
    </div>

    {{-- Search Form --}}
    <form method="GET" action="{{ route('super-admin.activity-logs') }}" style="display: flex; gap: 8px; max-width: 420px; margin-bottom: 16px;">
        <input type="hidden" name="role" value="{{ $roleFilter }}">
        <input type="text" name="q" value="{{ $search }}" class="input-inline" placeholder="Cari aksi, nama user, atau deskripsi..." style="flex: 1;">
        <button type="submit" class="btn btn-sm btn-brand">Cari</button>
        @if ($search)
            <a href="{{ route('super-admin.activity-logs', ['role' => $roleFilter]) }}" class="btn btn-sm">Reset</a>
        @endif
    </form>
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

    <div style="margin-top: 16px;">
        {{ $logs->links() }}
    </div>
</div>
@endsection
