@extends('layouts.app')

@section('title', 'Log Aktivitas Sistem (Audit Trail)')

@section('content')
<style>
    .log-row {
        padding: 14px 16px;
        border-bottom: 1px solid var(--border-color);
        transition: background .15s ease;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .log-row:nth-child(even) {
        background: color-mix(in srgb, var(--text-primary) 1.8%, transparent);
    }
    .log-row:hover {
        background: var(--bg-card-hover);
    }
    .log-row:last-of-type {
        border-bottom: 0;
    }
</style>
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

<div class="card" data-live-target style="padding-top: 4px; padding-bottom: 4px;">
    <div style="font-size: 12.5px; color: var(--text-muted); padding: 8px 14px 4px;">
        {{ $logs->total() }} aktivitas
    </div>

    @forelse ($logs as $log)
        @php $url = $log->subjectUrl(); @endphp
        <div class="log-row" onclick="document.getElementById('modal-log-{{ $log->id }}').showModal()">
            <div style="font-size: 13.5px; font-weight: 600; color: var(--text-primary); overflow-wrap: anywhere; line-height: 1.4;">
                {{ $log->description }}
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 6px 12px; align-items: center; font-size: var(--fs-xs); color: var(--text-muted);">
                <span style="font-weight: 600; color: var(--text-secondary); display: inline-flex; align-items: center; gap: 4px;">
                    <i class="ti ti-user" style="font-size: 13px;"></i> {{ $log->user?->name ?? 'Sistem' }}
                </span>
                @if ($log->user?->username)
                    <span class="dash-sub">{{ '@'.$log->user->username }}</span>
                @endif
                <span class="badge badge-netral">{{ \App\Models\User::LABELS[$log->role] ?? ucfirst($log->role) }}</span>
                <span style="display: inline-flex; align-items: center; gap: 3px;" title="{{ $log->action }}">
                    <i class="ti ti-bolt" style="color: var(--accent); font-size: 12px;"></i> {{ \App\Models\ActivityLog::actionLabel($log->action) }}
                </span>
                <time datetime="{{ $log->created_at->toIso8601String() }}" title="{{ $log->created_at->format('d/m/Y H:i:s') }}" style="display: inline-flex; align-items: center; gap: 3px;">
                    <i class="ti ti-clock" style="font-size: 12px;"></i> {{ $log->created_at->locale('id')->diffForHumans() }}
                </time>
                <span style="display: inline-flex; align-items: center; gap: 4px;" title="{{ $log->user_agent ?? 'Internal' }}">
                    <i class="ti {{ $log->ikonPerangkat() }}" style="font-size: 13px;"></i> {{ $log->ringkasanPerangkat() }}
                </span>
                @if (!empty($log->properties))
                    <span style="display: inline-flex; align-items: center; gap: 3px; color: var(--accent); font-weight: 500;" title="Klik baris untuk melihat parameter lengkap">
                        <i class="ti ti-code"></i> Parameter
                    </span>
                @endif
                @if ($url)
                    <a href="{{ $url }}" onclick="event.stopPropagation()" style="color: var(--accent); text-decoration: none; display: inline-flex; align-items: center; gap: 3px;">
                        <i class="ti ti-external-link"></i> Buka {{ ['User' => 'akun', 'ExtrasProfile' => 'profil Extras', 'CastingProject' => 'proyek', 'ProjectApplication' => 'pendaftaran', 'Attendance' => 'absensi', 'Contract' => 'kontrak', 'Invoice' => 'invoice', 'FeeNegotiation' => 'nego'][class_basename($log->subject_type)] ?? 'subjek' }}
                    </a>
                @endif
            </div>
        </div>

        {{-- Pop-up Modal M3 Detail Aktivitas --}}
        <dialog id="modal-log-{{ $log->id }}" class="dash-dialog" style="max-width: 600px; width: 95%; border-radius: var(--radius-lg); border: 1px solid var(--border-color); background: var(--bg-card); box-shadow: var(--shadow-modal);" onclick="if (event.target === this) this.close()">
            <div class="dash-dialog-isi" style="display: flex; flex-direction: column; gap: 14px; max-height: 85vh; overflow-y: auto;">
                {{-- Header Modal --}}
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                            <h3 style="margin: 0; font-size: var(--fs-md); font-weight: 700; color: var(--text-primary); line-height: 1.2;">
                                Detail Aktivitas
                            </h3>
                            <span class="badge badge-netral">{{ \App\Models\ActivityLog::actionLabel($log->action) }}</span>
                        </div>
                        <div style="font-size: var(--fs-xs); color: var(--text-muted); margin-top: 3px;">
                            ID #{{ $log->id }} · {{ $log->created_at->translatedFormat('d F Y, H:i:s') }} WIB ({{ $log->created_at->locale('id')->diffForHumans() }})
                        </div>
                    </div>
                    <button type="button" class="btn-ikon" onclick="this.closest('dialog').close()" aria-label="Tutup" style="border: none; background: transparent; cursor: pointer; font-size: 18px; color: var(--text-muted); padding: 4px; flex-shrink: 0;">
                        <i class="ti ti-x"></i>
                    </button>
                </div>

                {{-- Grid Data Aktor & Perangkat --}}
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; background: var(--md-surface-container, var(--bg-card-hover)); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 12px;">
                    <div>
                        <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: .03em;">Aktor Pelaksana</div>
                        <div style="font-size: var(--fs-sm); font-weight: 600; color: var(--text-primary); margin-top: 2px;">
                            {{ $log->user?->name ?? 'Sistem Internal' }}
                        </div>
                        <div style="font-size: var(--fs-xs); color: var(--text-muted); margin-top: 1px;">
                            {{ implode(' · ', array_filter([$log->user?->username ? '@'.$log->user->username : null, \App\Models\User::LABELS[$log->role] ?? ucfirst($log->role)])) }}
                        </div>
                    </div>

                    <div>
                        <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: .03em;">Waktu Eksekusi</div>
                        <div style="font-size: var(--fs-sm); font-weight: 600; color: var(--text-primary); margin-top: 2px;">
                            {{ $log->created_at->translatedFormat('d M Y, H:i:s') }}
                        </div>
                        <div style="font-size: var(--fs-xs); color: var(--text-muted); margin-top: 1px;">
                            {{ $log->created_at->locale('id')->diffForHumans() }}
                        </div>
                    </div>

                    <div>
                        <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: .03em;">Perangkat</div>
                        <div style="font-size: var(--fs-sm); font-weight: 600; color: var(--text-primary); margin-top: 2px; display: flex; align-items: center; gap: 4px;">
                            <i class="ti {{ $log->ikonPerangkat() }}"></i> {{ $log->ringkasanPerangkat() }}
                        </div>
                    </div>

                    <div>
                        <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: .03em;">Alamat IP</div>
                        <div style="font-size: var(--fs-sm); font-family: monospace; color: var(--text-primary); margin-top: 2px;">
                            {{ $log->ip_address ?? '–' }}
                        </div>
                    </div>
                </div>

                {{-- Deskripsi Aktivitas --}}
                <div>
                    <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: .03em; margin-bottom: 4px;">Deskripsi Aktivitas</div>
                    <div style="font-size: var(--fs-sm); color: var(--text-primary); line-height: 1.5; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 10px 12px;">
                        {{ $log->description }}
                    </div>
                </div>

                @if ($log->user_agent)
                    <div>
                        <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: .03em; margin-bottom: 4px;">User Agent Lengkap</div>
                        <div style="font-size: 11px; font-family: monospace; color: var(--text-secondary); background: var(--bg-page); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 8px 10px; word-break: break-all; line-height: 1.4;">
                            {{ $log->user_agent }}
                        </div>
                    </div>
                @endif

                {{-- Parameter & Detail Perubahan --}}
                <div>
                    <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: .03em; margin-bottom: 4px;">Parameter &amp; Detail Perubahan</div>
                    @if (!empty($log->properties))
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            @if (isset($log->properties['role_lama']) && isset($log->properties['role_baru']))
                                <div style="font-size: var(--fs-xs); padding: 8px 10px; background: var(--bg-card-hover); border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                                    <strong>Perubahan Role:</strong> <span class="badge badge-netral">{{ $log->properties['role_lama'] }}</span> &rarr; <span class="badge badge-brand">{{ $log->properties['role_baru'] }}</span>
                                </div>
                            @endif
                            @if (isset($log->properties['grade_lama']) || isset($log->properties['grade_baru']))
                                <div style="font-size: var(--fs-xs); padding: 8px 10px; background: var(--bg-card-hover); border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                                    <strong>Perubahan Grade:</strong> <span class="badge badge-netral">{{ $log->properties['grade_lama'] ?? 'Belum dinilai' }}</span> &rarr; <span class="badge badge-brand">{{ $log->properties['grade_baru'] ?? 'Belum dinilai' }}</span>
                                </div>
                            @endif
                            <pre style="background: var(--bg-page); padding: 10px; border-radius: var(--radius-sm); margin: 0; font-size: 12px; font-family: monospace; overflow-x: auto; white-space: pre-wrap; word-break: break-all; border: 1px solid var(--border-color);">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    @else
                        <div style="font-size: var(--fs-xs); color: var(--text-muted); font-style: italic;">
                            Tidak ada parameter tambahan yang dicatat untuk aktivitas ini.
                        </div>
                    @endif
                </div>

                {{-- Footer Modal --}}
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 4px; padding-top: 12px; border-top: 1px solid var(--border-color);">
                    <button type="button" class="btn btn-sm btn-tonal" onclick="this.closest('dialog').close()">
                        Tutup
                    </button>
                    @if ($url)
                        <a href="{{ $url }}" class="btn btn-sm btn-brand">
                            Buka {{ ['User' => 'Akun', 'ExtrasProfile' => 'Profil Extras', 'CastingProject' => 'Proyek', 'ProjectApplication' => 'Pendaftaran', 'Attendance' => 'Absensi', 'Contract' => 'Kontrak', 'Invoice' => 'Invoice', 'FeeNegotiation' => 'Nego'][class_basename($log->subject_type)] ?? 'Subjek' }} &rarr;
                        </a>
                    @endif
                </div>
            </div>
        </dialog>
    @empty
        <p style="text-align: center; color: var(--text-muted); padding: 24px 0;">Belum ada catatan aktivitas yang sesuai filter.</p>
    @endforelse

    <x-pagination-bar :paginator="$logs" :pilihan="\App\Support\PerHalaman::TABEL" />
</div>
@endsection
