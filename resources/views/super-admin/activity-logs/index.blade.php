@extends('layouts.app')

@section('title', 'Log Aktivitas Sistem (Audit Trail)')

@section('content')
<form method="GET" action="{{ route('super-admin.activity-logs') }}" class="xtoolbar" data-live>
    <input type="search" name="q" value="{{ $f['q'] ?? '' }}" class="xtoolbar-cari" placeholder="Cari deskripsi, aktor, atau subjek…" aria-label="Cari log">
    <button type="submit" class="btn btn-sm btn-brand" aria-label="Cari"><i class="ti ti-search"></i></button>
    <details class="xtoolbar-more" @if($filterAktif) open @endif>
        <summary class="btn btn-sm" aria-label="Filter"><i class="ti ti-adjustments-horizontal"></i> Filter @if($filterAktif)<span class="badge badge-netral">{{ $filterAktif }}</span>@endif</summary>
        <div class="xtoolbar-more-isi">
            <input type="text" name="aktor" value="{{ $f['aktor'] ?? '' }}" list="log-aktor-list" placeholder="Aktor" aria-label="Aktor" style="flex: 1 1 160px;">
            <datalist id="log-aktor-list">
                @foreach ($aktorList as $nama)<option value="{{ $nama }}">@endforeach
            </datalist>
            <select name="role" aria-label="Role" style="flex: 1 1 140px;">
                <option value="">Semua role</option>
                @foreach (\App\Models\User::LABELS as $val => $label)
                    <option value="{{ $val }}" @selected(($f['role'] ?? '') === $val)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="aksi" aria-label="Jenis aksi" style="flex: 1 1 180px;">
                <option value="">Semua aksi</option>
                @foreach ($aksiList as $kode)
                    <option value="{{ $kode }}" title="{{ $kode }}" @selected(($f['aksi'] ?? '') === $kode)>{{ \App\Models\ActivityLog::actionLabel($kode) }}</option>
                @endforeach
            </select>
            <span style="display: flex; gap: 6px; align-items: center; flex: 1 1 100%; flex-wrap: wrap;">
                <input type="date" name="dari" value="{{ $f['dari'] ?? '' }}" aria-label="Dari tanggal" style="flex: 1 1 130px;">
                <span style="color: var(--text-muted);">s/d</span>
                <input type="date" name="sampai" value="{{ $f['sampai'] ?? '' }}" aria-label="Sampai tanggal" style="flex: 1 1 130px;">
            </span>
            <button type="submit" class="btn btn-sm">Terapkan</button>
            @if($filterAktif || !empty($f['q']))
                <a href="{{ route('super-admin.activity-logs') }}" class="btn btn-sm">Reset</a>
            @endif
        </div>
    </details>
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

    <div style="margin-top: 16px;">{{ $logs->links() }}</div>
</div>
@endsection
