@extends('layouts.app')

@section('title', 'Detail ' . $user->name)

@section('content')
<div style="margin-bottom: 20px;">
    <a href="{{ route('super-admin.admins.index') }}" style="color: var(--accent); font-size: 13px;">← Kembali</a>
    <h2 style="margin: 8px 0;">{{ $user->name }}</h2>
    <div style="color: var(--text-muted); font-size: 13px;">
        {{ $user->email }} • <span class="badge badge-pending">{{ $user->role }}</span>
        <span class="badge {{ $user->status === 'aktif' ? 'badge-aktif' : 'badge-tolak' }}">{{ $user->status }}</span>
    </div>
</div>

@if (session('status'))
    <div class="alert-success" style="margin-bottom: 12px;">{{ session('status') }}</div>
@endif

<div class="card" style="margin-bottom: 16px;">
    <div style="font-weight: 600; margin-bottom: 12px;">Profil</div>
    <table style="width: 100%; font-size: 13px;">
        <tr><td style="padding: 4px 0; width: 120px; color: var(--text-muted);">Nama</td><td>{{ $user->name }}</td></tr>
        <tr><td style="padding: 4px 0; color: var(--text-muted);">Email</td><td>{{ $user->email }}</td></tr>
        <tr><td style="padding: 4px 0; color: var(--text-muted);">Role</td><td>{{ $user->role }}</td></tr>
        <tr><td style="padding: 4px 0; color: var(--text-muted);">Status</td><td><span class="badge {{ $user->status === 'aktif' ? 'badge-aktif' : 'badge-tolak' }}">{{ $user->status }}</span></td></tr>
        @if ($user->adminProfile && $user->adminProfile->honor_nominal)
        <tr><td style="padding: 4px 0; color: var(--text-muted);">Honor/Event</td><td>Rp {{ number_format($user->adminProfile->honor_nominal, 0, ',', '.') }}</td></tr>
        @endif
        <tr><td style="padding: 4px 0; color: var(--text-muted);">Bergabung</td><td>{{ $user->created_at->format('d M Y') }}</td></tr>
    </table>
</div>

{{-- 2a: Kinerja + Edit Honor untuk Admin & Korlap --}}
@if (in_array($user->role, ['admin', 'admin_default', 'admin_talco', 'admin_sosmed', 'korlap', 'admin_korlap']))
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

    @if ($user->adminProfile)
    <div class="card" style="margin-bottom: 16px;">
        <div style="font-weight: 600; margin-bottom: 10px;">Edit Honor per Event</div>
        <form method="POST" action="{{ route('super-admin.admins.honor', $user) }}" style="display: flex; gap: 8px; align-items: flex-end; flex-wrap: wrap;">
            @csrf @method('PATCH')
            <div style="flex: 1; min-width: 160px;">
                <label style="font-size: 12px; color: var(--text-muted); display: block; margin-bottom: 4px;">Nominal Honor (Rp)</label>
                <input type="number" name="honor_nominal" min="0" value="{{ $user->adminProfile->honor_nominal ?? 0 }}" style="margin: 0;">
            </div>
            <button type="submit" class="btn btn-sm btn-brand">Simpan</button>
        </form>
    </div>
    @endif
@endif

{{-- Riwayat Proyek (Admin/Korlap/Client) --}}
@if ($user->role !== 'extras')
<div class="card" style="margin-bottom: 16px;">
    <div style="font-weight: 600; margin-bottom: 12px;">Riwayat Proyek</div>
    @if ($assignments->isEmpty())
        <div style="color: var(--text-muted); text-align: center; padding: 20px; font-size: 13px;">Belum ada penugasan.</div>
    @else
        <table style="width: 100%; font-size: 13px;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <th style="text-align: left; padding: 8px 0;">Proyek</th>
                    <th style="text-align: left; padding: 8px 0;">Status</th>
                    @if ($user->isCastingDirector())
                        <th style="text-align: left; padding: 8px 0;">Review</th>
                    @else
                        <th style="text-align: left; padding: 8px 0;">Mulai</th>
                        <th style="text-align: left; padding: 8px 0;">Honor</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($assignments as $a)
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 8px 0;"><a href="{{ route('admin.projects.applicants', $a->castingProject) }}" style="color: var(--accent);">{{ $a->castingProject->nama_produksi }}</a></td>
                    <td style="padding: 8px 0;"><span class="badge {{ $a->status_log === 'berjalan' ? 'badge-warning' : 'badge-aktif' }}">{{ $a->status_log }}</span></td>
                    @if ($user->isCastingDirector())
                        <td style="padding: 8px 0;">@php $cnt = $a->cdReviews()->count(); @endphp {{ $cnt }} review</td>
                    @else
                        <td style="padding: 8px 0;">{{ $a->created_at->format('d M') }}</td>
                        <td style="padding: 8px 0;">@if ($a->payroll) Rp {{ number_format($a->payroll->nominalTotal(), 0, ',', '.') }} @else - @endif</td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endif

{{-- 2b: Client — Riwayat Proyek yang Diajukan --}}
@if ($user->isClient() && $clientProjects)
<div class="card" style="margin-bottom: 16px;">
    <div style="font-weight: 600; margin-bottom: 12px;">Proyek yang Diajukan</div>
    @if ($clientProjects->isEmpty())
        <div style="color: var(--text-muted); text-align: center; padding: 20px; font-size: 13px;">Belum ada proyek.</div>
    @else
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
                    <td style="padding: 8px 0;">{{ $p->nama_produksi }}</td>
                    <td style="padding: 8px 0;"><span class="badge badge-pending">{{ $p->status }}</span></td>
                    <td style="padding: 8px 0;">{{ $p->deadline_pengambilan ? \Carbon\Carbon::parse($p->deadline_pengambilan)->format('d M Y') : '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
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
        <a href="{{ route('admin.extras.profil', $user) }}" class="btn btn-sm btn-brand">Lihat Profil Lengkap</a>
    @else
        <div style="color: var(--text-muted); font-size: 13px;">Profil belum diisi.</div>
    @endif
</div>

@if ($availableKategori && $user->extrasProfile)
<div class="card" style="margin-bottom: 16px;">
    <div style="font-weight: 600; margin-bottom: 10px;">Kategori Extras</div>
    <form method="POST" action="{{ route('super-admin.admins.kategori', $user) }}">
        @csrf @method('PATCH')
        <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px;">
            @foreach ($availableKategori as $kat)
                <label style="font-size: 12.5px; display: flex; align-items: center; gap: 4px;">
                    <input type="checkbox" name="kategori_ids[]" value="{{ $kat->id }}"
                        {{ $user->extrasProfile->categories->contains('id', $kat->id) ? 'checked' : '' }}>
                    {{ $kat->nama }}
                </label>
            @endforeach
        </div>
        <button type="submit" class="btn btn-sm">Simpan Kategori</button>
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
    @endif
@endif

{{-- AU.6.7: Aktivitas Akun Ini --}}
@if (isset($userActivities) && $userActivities->isNotEmpty())
<div class="card" style="margin-bottom: 16px;">
    <div style="font-weight: 600; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
        <i class="ti ti-activity" style="color: var(--accent);"></i> Aktivitas Akun Ini (Audit Trail)
    </div>
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
                    <td style="padding: 8px 0;"><span class="badge badge-pending">{{ $act->action }}</span></td>
                    <td style="padding: 8px 0;">{{ $act->description }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="card">
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <button type="button" class="btn btn-sm" onclick="document.getElementById('toggle-dialog').showModal()">{{ $user->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}</button>
        <form method="POST" action="{{ route('super-admin.admins.reset-password', $user) }}" style="display:inline;" onsubmit="return confirm('Reset password akun ini? Password baru akan ditampilkan sekali.')">
            @csrf
            <button type="submit" class="btn btn-sm">Reset Password</button>
        </form>
        @if (!$user->is_protected && !$user->has_history)
            <button type="button" class="btn btn-sm btn-danger-outline" onclick="document.getElementById('delete-dialog').showModal()">Hapus</button>
        @endif
    </div>
</div>

<dialog id="toggle-dialog" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 360px; width: 90%;">
    <form method="POST" action="{{ route('super-admin.admins.toggle-status', $user) }}" style="padding: 18px;">
        @csrf @method('PATCH')
        <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">{{ $user->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }} {{ $user->name }}?</div>
        <div style="display: flex; gap: 8px; justify-content: flex-end;">
            <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
            <button type="submit" class="btn btn-sm btn-brand">Lanjut</button>
        </div>
    </form>
</dialog>

@if (!$user->is_protected && !$user->has_history)
<dialog id="delete-dialog" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 360px; width: 90%;">
    <form method="POST" action="{{ route('super-admin.admins.destroy', $user) }}" style="padding: 18px;">
        @csrf @method('DELETE')
        <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">Hapus {{ $user->name }}?</div>
        <div style="color: var(--text-muted); font-size: 12px; margin-bottom: 12px;">Tidak bisa dibatalkan.</div>
        <div style="display: flex; gap: 8px; justify-content: flex-end;">
            <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
            <button type="submit" class="btn btn-sm btn-danger-outline">Hapus</button>
        </div>
    </form>
</dialog>
@endif
@endsection
