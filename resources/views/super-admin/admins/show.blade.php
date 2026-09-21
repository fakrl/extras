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

<div class="card" style="margin-bottom: 16px;">
    <div style="font-weight: 600; margin-bottom: 12px;">Riwayat Proyek</div>
    @if ($assignments->isEmpty())
        <div style="color: var(--text-muted); text-align: center; padding: 20px; font-size: 13px;">Belum ada penugasan.</div>
    @else
        <table style="width: 100%; font-size: 13px;">
            <thead><tr style="border-bottom: 1px solid var(--border-color);"><th style="text-align: left; padding: 8px 0;">Proyek</th><th style="text-align: left; padding: 8px 0;">Status</th>@if ($user->isCastingDirector())<th style="text-align: left; padding: 8px 0;">Review</th>@else<th style="text-align: left; padding: 8px 0;">Mulai</th><th style="text-align: left; padding: 8px 0;">Honor</th>@endif</tr></thead>
            <tbody>
                @foreach ($assignments as $a)
                <tr style="border-bottom: 1px solid var(--border-color);"><td style="padding: 8px 0;"><a href="{{ route('admin.projects.applicants', $a->castingProject) }}" style="color: var(--accent);">{{ $a->castingProject->nama_produksi }}</a></td><td style="padding: 8px 0;"><span class="badge {{ $a->status_log === 'berjalan' ? 'badge-warning' : 'badge-aktif' }}">{{ $a->status_log }}</span></td>@if ($user->isCastingDirector())<td style="padding: 8px 0;">@php $cnt = $a->cdReviews()->count(); @endphp {{ $cnt }} review</td>@else<td style="padding: 8px 0;">{{ $a->created_at->format('d M') }}</td><td style="padding: 8px 0;">@if ($a->payroll) Rp {{ number_format($a->payroll->nominalTotal(), 0, ',', '.') }} @else - @endif</td>@endif</tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

@if (!$user->isClient())
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

<div class="card">
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <button type="button" class="btn btn-sm" onclick="document.getElementById('toggle-dialog').showModal()">{{ $user->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}</button>
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
