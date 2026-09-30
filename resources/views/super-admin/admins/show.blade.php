@extends('layouts.app')

@section('title', 'Detail ' . $user->name)

@section('content')
<div style="margin-bottom: 20px;">
    <a href="{{ route('super-admin.akun.index', ['role' => $user->role]) }}" style="color: var(--accent); font-size: 13px;">← Manajemen Akun</a>
    <h2 style="margin: 8px 0;">{{ $user->name }}</h2>
    <div style="color: var(--text-muted); font-size: 13px;">
        {{ $user->email ?? ($user->username ? '@'.$user->username : '') }} • <x-status-badge :model="$user" />
        <span class="badge {{ $user->status === 'aktif' ? 'badge-aktif' : 'badge-tolak' }}">{{ $user->status }}</span>
    </div>
</div>

@if (session('status'))
    <div class="alert-success" style="margin-bottom: 12px;">{{ session('status') }}</div>
@endif

<div class="card" style="margin-bottom: 16px;">
    <div style="font-weight: 600; margin-bottom: 12px;">Profil</div>
    <div class="table-container">
    <table style="width: 100%; font-size: 13px;">
        <tr><td style="padding: 4px 0; width: 120px; color: var(--text-muted);">Nama</td><td>{{ $user->name }}</td></tr>
        <tr><td style="padding: 4px 0; color: var(--text-muted);">Username</td><td>{{ $user->username ? '@'.$user->username : '-' }}</td></tr>
        <tr><td style="padding: 4px 0; color: var(--text-muted);">Email</td><td>{{ $user->email ?? '-' }}</td></tr>
        <tr><td style="padding: 4px 0; color: var(--text-muted);">WA</td><td>{{ $user->nomor_wa ? '+'.$user->nomor_wa : '-' }}</td></tr>
        @if ($user->nama_perusahaan)
        <tr><td style="padding: 4px 0; color: var(--text-muted);">Perusahaan/PH</td><td>{{ $user->nama_perusahaan }}</td></tr>
        @endif
        <tr><td style="padding: 4px 0; color: var(--text-muted);">Role</td><td>{{ $user->label() }}</td></tr>
        <tr><td style="padding: 4px 0; color: var(--text-muted);">Status</td><td><span class="badge {{ $user->status === 'aktif' ? 'badge-aktif' : 'badge-tolak' }}">{{ $user->status }}</span></td></tr>
        @if ($user->honor_nominal)
        <tr><td style="padding: 4px 0; color: var(--text-muted);">Honor/Event</td><td>Rp {{ number_format($user->honor_nominal, 0, ',', '.') }}</td></tr>
        @endif
        <tr><td style="padding: 4px 0; color: var(--text-muted);">Bergabung</td><td>{{ $user->created_at->format('d M Y') }}</td></tr>
    </table>
    </div>
</div>

{{-- 2a: Kinerja + Edit Honor untuk Admin & Korlap --}}
@if (in_array($user->role, ['admin', 'korlap']))
    @php
        $totalProyek = $assignments->count();
        $selesai = $assignments->where('status_log', 'selesai_produksi')->count();
        $berjalan = $totalProyek - $selesai;
    @endphp
    <div class="card" style="margin-bottom: 16px;">
        <div style="font-weight: 600; margin-bottom: 12px;"><i class="ti ti-chart-bar" style="color: var(--accent);"></i> Kinerja</div>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; font-size: 13px; text-align: center;">
            <div><div style="font-size: 22px; font-weight: 700; color: var(--accent);">{{ $totalProyek }}</div><div style="color: var(--text-muted);">Total Proyek</div></div>
            <div><div style="font-size: 22px; font-weight: 700; color: var(--accent);">{{ $selesai }}</div><div style="color: var(--text-muted);">Selesai</div></div>
            <div><div style="font-size: 22px; font-weight: 700;">{{ $berjalan }}</div><div style="color: var(--text-muted);">Berjalan</div></div>
        </div>
    </div>

    <div class="card" style="margin-bottom: 16px;">
        <div style="font-weight: 600; margin-bottom: 10px;">Edit Honor per Event</div>
        <form method="POST" action="{{ route('super-admin.admins.honor', $user) }}" style="display: flex; gap: 8px; align-items: flex-end; flex-wrap: wrap;">
            @csrf @method('PATCH')
            <div style="flex: 1; min-width: 160px;">
                <label style="font-size: 12px; color: var(--text-muted); display: block; margin-bottom: 4px;">Nominal Honor (Rp)</label>
                <input type="number" name="honor_nominal" min="0" value="{{ $user->honor_nominal ?? 0 }}" style="margin: 0;">
            </div>
            <button type="submit" class="btn btn-sm btn-brand">Simpan</button>
        </form>
    </div>
@endif

{{-- Riwayat Proyek (Admin/Korlap) --}}
@if (! $user->isClient() && $user->role !== 'extras')
<div class="card" style="margin-bottom: 16px;">
    <div style="font-weight: 600; margin-bottom: 12px;">Riwayat Proyek</div>
    @if ($assignments->isEmpty())
        <div style="color: var(--text-muted); text-align: center; padding: 20px; font-size: 13px;">Belum ada penugasan.</div>
    @else
        <div class="table-container">
        <table style="width: 100%; font-size: 13px;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <th style="text-align: left; padding: 8px 0;">Proyek</th>
                    <th style="text-align: left; padding: 8px 0;">Status</th>
                    <th style="text-align: left; padding: 8px 0;">Mulai</th>
                    <th style="text-align: left; padding: 8px 0;">Honor</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($assignments as $a)
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 8px 0;"><a href="{{ route('admin.projects.applicants', $a->castingProject) }}" style="color: var(--accent);">{{ $a->castingProject->nama_produksi }}</a></td>
                    <td style="padding: 8px 0;"><span class="badge {{ $a->status_log === 'berjalan' ? 'badge-warning' : 'badge-aktif' }}">{{ $a->status_log }}</span></td>
                    <td style="padding: 8px 0;">{{ $a->created_at->format('d M') }}</td>
                    <td style="padding: 8px 0;">@if ($a->payroll) Rp {{ number_format($a->payroll->nominalTotal(), 0, ',', '.') }} @else - @endif</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    @endif
</div>
@endif

{{-- 2b: Client — proyek milik akun ini --}}
@if ($user->isClient() && $clientProjects)
<div class="card" style="margin-bottom: 16px;">
    <div style="font-weight: 600; margin-bottom: 12px;">Proyek Client</div>
    @if ($clientProjects->isEmpty())
        <div style="color: var(--text-muted); text-align: center; padding: 20px; font-size: 13px;">Belum ada proyek.</div>
    @else
        <div class="table-container">
        <table style="width: 100%; font-size: 13px;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <th style="text-align: left; padding: 8px 0;">Nama Produksi</th>
                    <th style="text-align: left; padding: 8px 0;">Status</th>
                    <th style="text-align: left; padding: 8px 0;">Deadline</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($clientProjects as $p)
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 8px 0;"><a href="{{ route('admin.projects.show', $p) }}" style="color: var(--accent);">{{ $p->nama_produksi }}</a></td>
                    <td style="padding: 8px 0;"><span class="badge badge-pending">{{ $p->status }}</span></td>
                    <td style="padding: 8px 0;">{{ $p->deadline?->format('d M Y') ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    @endif
</div>
@endif

{{-- 2c: Extras — Profil + Toggle Status + Update Kategori --}}
@if ($user->role === 'extras')
<div class="card" style="margin-bottom: 16px;">
    <div style="font-weight: 600; margin-bottom: 10px;">Profil Extras</div>
    @if ($user->extrasProfile)
        <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 8px;">
            Alias: {{ $user->extrasProfile->user->username ?? '-' }} &bull;
            Grade: {{ $user->extrasProfile->grade_saat_ini ?? 'Belum dinilai' }}
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
            <a href="{{ route('admin.extras.profil', $user) }}" class="btn btn-sm btn-brand" data-profil-modal>Lihat Profil Lengkap</a>
            @include('partials.toggle-beranda', ['profile' => $user->extrasProfile])
        </div>
        <div style="font-weight: 600; margin: 16px 0 8px;">Riwayat Proyek</div>
        @forelse ($user->extrasProfile->applications->sortByDesc('id') as $app)
            <div style="display: flex; justify-content: space-between; gap: 8px; padding: 6px 0; border-top: 1px solid var(--border-color); font-size: 13px;">
                <span style="overflow-wrap: anywhere;">{{ $app->castingProject?->nama_produksi ?? '-' }}</span>
                <span class="badge {{ $app->badgeClass() }}">{{ $app->label() }}</span>
            </div>
        @empty
            <div style="color: var(--text-muted); font-size: 13px;">Belum pernah daftar proyek.</div>
        @endforelse
    @else
        <div style="color: var(--text-muted); font-size: 13px;">Profil belum diisi.</div>
    @endif
</div>

@if ($availableKategori && $user->extrasProfile)
<div class="card" style="margin-bottom: 16px;">
    <div style="font-weight: 600; margin-bottom: 10px;">Tag Extras</div>
    <form method="POST" action="{{ route('super-admin.admins.kategori', $user) }}">
        @csrf @method('PATCH')
        @include('partials.tag-input', ['name' => 'tag_nama', 'selected' => $user->extrasProfile->categories, 'tagGroups' => $availableKategori])
        <button type="submit" class="btn btn-sm btn-brand" style="margin-top: 12px;">Simpan Tag</button>
    </form>
</div>
@endif
@endif

{{-- Reimbursement (non-client, non-extras) --}}
@if (!$user->isClient() && $user->role !== 'extras')
    @php
        $staffReimbursements = $assignments->flatMap(function ($a) {
            return $a->payroll?->addons->map(function ($addon) use ($a) {
                return (object) [
                    'proyek' => $a->castingProject->nama_produksi,
                    'label' => $addon->label,
                    'nominal' => $addon->nominal,
                    'tanggal' => $addon->created_at,
                ];
            }) ?? collect();
        })->sortByDesc('tanggal');
    @endphp
    @if ($staffReimbursements->isNotEmpty())
        <div class="card" style="margin-bottom: 16px;">
            <div style="font-weight: 600; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                <i class="ti ti-receipt" style="color: var(--accent);"></i> Riwayat Reimbursement &amp; Biaya Tambahan
            </div>
            <div class="table-container">
            <table style="width: 100%; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <th style="text-align: left; padding: 8px 0;">Tanggal</th>
                        <th style="text-align: left; padding: 8px 0;">Proyek</th>
                        <th style="text-align: left; padding: 8px 0;">Keperluan</th>
                        <th style="text-align: right; padding: 8px 0;">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($staffReimbursements as $sr)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 8px 0;">{{ $sr->tanggal ? $sr->tanggal->format('d M Y') : '-' }}</td>
                            <td style="padding: 8px 0;">{{ $sr->proyek }}</td>
                            <td style="padding: 8px 0; font-weight: 500;">{{ $sr->label }}</td>
                            <td style="padding: 8px 0; text-align: right; font-weight: 600; color: var(--accent-strong);">
                                Rp {{ number_format($sr->nominal, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
    @endif
@endif

{{-- AU.6.7: Aktivitas Akun Ini --}}
<div class="card" style="margin-bottom: 16px;">
    <div style="font-weight: 600; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
        <i class="ti ti-activity" style="color: var(--accent);"></i> Aktivitas Akun Ini (Audit Trail)
    </div>
    @if ($userActivities->isEmpty())
        <div style="color: var(--text-muted); text-align: center; padding: 20px; font-size: 13px;">Belum ada aktivitas.</div>
    @else
    <div class="table-container">
    <table style="width: 100%; font-size: 13px;">
        <thead>
            <tr style="border-bottom: 1px solid var(--border-color);">
                <th style="text-align: left; padding: 8px 0;">Waktu</th>
                <th style="text-align: left; padding: 8px 0;">Aktivitas</th>
                <th style="text-align: left; padding: 8px 0;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($userActivities as $act)
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 8px 0; color: var(--text-muted); font-size: 12px; white-space: nowrap;">{{ $act->created_at->format('d M Y H:i') }}</td>
                    <td style="padding: 8px 0;"><span class="badge badge-netral" title="{{ $act->action }}">{{ \App\Models\ActivityLog::actionLabel($act->action) }}</span></td>
                    <td style="padding: 8px 0;">{{ $act->description }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    @endif
</div>

{{-- BM.1: notifikasi akun + status kirim WA/email, biar yang gagal bisa dikabari manual --}}
<div class="card" style="margin-bottom: 16px;">
    <div style="font-weight: 600; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
        <i class="ti ti-bell" style="color: var(--accent);"></i> Notifikasi Terakhir
    </div>
    @if ($notifikasi->isEmpty())
        <div style="color: var(--text-muted); text-align: center; padding: 20px; font-size: 13px;">Belum ada notifikasi.</div>
    @else
    <div class="table-container">
    <table style="width: 100%; font-size: 13px;">
        <thead>
            <tr style="border-bottom: 1px solid var(--border-color);">
                <th style="text-align: left; padding: 8px 0;">Waktu</th>
                <th style="text-align: left; padding: 8px 0;">Notifikasi</th>
                <th style="text-align: left; padding: 8px 0;">Kirim</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($notifikasi as $n)
                @php
                    $kanal = collect(['wa' => ['ti-brand-whatsapp', 'WA'], 'email' => ['ti-mail', 'Email']])
                        ->filter(fn ($_, $k) => array_key_exists($k, $n->data));
                @endphp
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 8px 0; color: var(--text-muted); font-size: 12px; white-space: nowrap;">{{ $n->created_at->format('d M Y H:i') }}</td>
                    <td style="padding: 8px 0;"><strong>{{ $n->data['judul'] ?? '-' }}</strong><div style="color: var(--text-muted);">{{ $n->data['pesan'] ?? '' }}</div></td>
                    <td style="padding: 8px 0; white-space: nowrap;">
                        @forelse ($kanal as $k => [$ikon, $nama])
                            @php $st = $n->data[$k]; @endphp
                            <span data-status-{{ $k }}="{{ $st ?? 'antre' }}" title="{{ $nama }}: {{ $st ?? 'antre' }}{{ $k === 'wa' && ! empty($n->data['wa_dikirim_at']) ? ' ('.$n->data['wa_dikirim_at'].')' : '' }}"
                                  style="color: {{ $st === 'terkirim' ? 'var(--accent)' : ($st === 'gagal' ? 'var(--danger)' : 'var(--text-muted)') }};">
                                <i class="ti {{ $ikon }}" aria-hidden="true"></i><i class="ti {{ $st === 'terkirim' ? 'ti-check' : ($st === 'gagal' ? 'ti-x' : 'ti-clock') }}" aria-hidden="true"></i>
                            </span>
                        @empty
                            <span style="color: var(--text-muted);">-</span>
                        @endforelse
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    @endif
</div>

<div class="card">
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <button type="button" class="btn btn-sm" onclick="document.getElementById('toggle-dialog-{{ $user->id }}').showModal()">{{ $user->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}</button>
        <form method="POST" action="{{ route('super-admin.admins.reset-password', $user) }}" style="display:inline;" onsubmit="return confirm('Reset password akun ini? Password baru akan ditampilkan sekali.')">
            @csrf
            <button type="submit" class="btn btn-sm">Reset Password</button>
        </form>
        <button type="button" class="btn btn-sm" onclick="document.getElementById('edit-user-dialog-{{ $user->id }}').showModal()">Edit</button>
        <button type="button" class="btn btn-sm btn-danger-outline" onclick="document.getElementById('hapus-dialog-{{ $user->id }}').showModal()">Hapus</button>
    </div>
</div>

@include('super-admin.akun.aksi-dialogs', ['u' => $user])
@endsection
