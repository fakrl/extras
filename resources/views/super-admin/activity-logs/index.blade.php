@extends('layouts.app')

@section('title', 'Log Aktivitas Sistem (Audit Trail)')

@section('content')
@php $tgl = fn ($d) => \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M Y'); @endphp
<form method="GET" action="{{ route('super-admin.activity-logs') }}" class="xtoolbar" id="live-form" data-live>
    <input type="search" name="q" value="{{ $f['q'] ?? '' }}" class="xtoolbar-cari" placeholder="Cari deskripsi, aktor, atau subjek…" aria-label="Cari log">
    <x-per-halaman :pilihan="\App\Support\PerHalaman::TABEL" :nilai="$logs->perPage()" />
    <x-filter-panel :filter="[
        ! empty($f['aktor']) ? ['aktor', 'Aktor: '.$f['aktor']] : null,
        ! empty($f['role']) ? ['role', 'Role: '.(\App\Models\User::LABELS[$f['role']] ?? $f['role'])] : null,
        ! empty($f['aksi']) ? ['aksi', 'Aksi: '.\App\Models\ActivityLog::actionLabel($f['aksi'])] : null,
        ! empty($f['dari']) ? ['dari', 'Dari: '.$tgl($f['dari'])] : null,
        ! empty($f['sampai']) ? ['sampai', 'Sampai: '.$tgl($f['sampai'])] : null,
    ]">
        <div>
            <label class="fpanel-label" for="log-aktor">Aktor</label>
            <input type="text" id="log-aktor" name="aktor" value="{{ $f['aktor'] ?? '' }}" list="log-aktor-list" placeholder="Nama atau email">
            <datalist id="log-aktor-list">
                @foreach ($aktorList as $nama)<option value="{{ $nama }}">@endforeach
            </datalist>
        </div>
        <x-filter-panel.grup label="Role" name="role" :opsi="['' => 'Semua'] + \Illuminate\Support\Arr::except(\App\Models\User::LABELS, 'super_admin') + ['super_admin' => 'Super Admin']" :nilai="$f['role'] ?? null" baris />
        <div>
            <label class="fpanel-label" for="log-aksi">Jenis aksi</label>
            <select id="log-aksi" name="aksi">
                <option value="">Semua aksi</option>
                @foreach ($aksiList as $kode)
                    <option value="{{ $kode }}" title="{{ $kode }}" @selected(($f['aksi'] ?? '') === $kode)>{{ \App\Models\ActivityLog::actionLabel($kode) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <div class="fpanel-label">Rentang tanggal</div>
            <div class="fpanel-dua">
                <input type="date" name="dari" value="{{ $f['dari'] ?? '' }}" aria-label="Dari tanggal">
                <span style="color: var(--text-muted);">s/d</span>
                <input type="date" name="sampai" value="{{ $f['sampai'] ?? '' }}" aria-label="Sampai tanggal">
            </div>
        </div>
    </x-filter-panel>
</form>

<div class="card" data-live-target>
    <div style="font-size: 12.5px; color: var(--text-muted); margin-bottom: 10px;">
        {{ $logs->total() }} aktivitas
    </div>

    @forelse ($logs as $log)
        <div style="padding: 10px 0; border-top: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 4px; min-width: 0;">
            <div style="font-size: 13.5px; overflow-wrap: anywhere;">{{ $log->description }}</div>
            <div style="display: flex; flex-wrap: wrap; gap: 6px 10px; align-items: center; font-size: var(--fs-xs); color: var(--text-muted);">
                <span style="font-weight: 600; color: var(--text-secondary);">{{ $log->user?->name ?? 'Sistem' }}</span>
                <span class="badge badge-netral">{{ \App\Models\User::LABELS[$log->role] ?? ucfirst($log->role) }}</span>
                <span title="{{ $log->action }}">{{ \App\Models\ActivityLog::actionLabel($log->action) }}</span>
                <time datetime="{{ $log->created_at->toIso8601String() }}" title="{{ $log->created_at->format('d/m/Y H:i:s') }}">{{ $log->created_at->locale('id')->diffForHumans() }}</time>
                @if ($url = $log->subjectUrl())
                    <a href="{{ $url }}" style="color: var(--accent);"><i class="ti ti-external-link"></i> Buka {{ ['User' => 'akun', 'ExtrasProfile' => 'profil Extras', 'CastingProject' => 'proyek', 'ProjectApplication' => 'pendaftaran'][class_basename($log->subject_type)] ?? 'subjek' }}</a>
                @endif
            </div>
            @if (!empty($log->properties))
                <details style="font-size: var(--fs-xs); color: var(--text-muted);">
                    <summary style="cursor: pointer; color: var(--accent);">Lihat Parameter</summary>
                    <pre style="background: var(--bg-page); padding: 8px; border-radius: 6px; margin-top: 4px; overflow-x: auto; white-space: pre-wrap; overflow-wrap: anywhere;">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </details>
            @endif
        </div>
    @empty
        <p style="text-align: center; color: var(--text-muted); padding: 24px 0;">Belum ada catatan aktivitas yang sesuai filter.</p>
    @endforelse

    <x-pagination-bar :paginator="$logs" :pilihan="\App\Support\PerHalaman::TABEL" />
</div>
@endsection
