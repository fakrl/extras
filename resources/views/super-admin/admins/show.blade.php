@extends('layouts.app')

@section('title', 'Detail ' . $user->name)

@section('content')
<style>
    .akun-grid-dua {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
        margin-bottom: 16px;
        align-items: start;
    }
    @media (min-width: 861px) {
        .akun-grid-dua {
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        }
    }
    .custom-scroll-box {
        max-height: 260px;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: var(--border-color) transparent;
    }
    .custom-scroll-box::-webkit-scrollbar {
        width: 6px;
    }
    .custom-scroll-box::-webkit-scrollbar-thumb {
        background-color: var(--border-color);
        border-radius: 4px;
    }
    .riwayat-table-desktop {
        display: table;
        width: 100%;
        font-size: 13px;
        border-collapse: collapse;
    }
    .riwayat-cards-mobile {
        display: none;
    }
    @media (max-width: 640px) {
        .riwayat-table-desktop {
            display: none;
        }
        .riwayat-cards-mobile {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
    }
</style>

<div style="margin-bottom: 20px;">
    <a href="{{ route('super-admin.akun.index', ['role' => $user->role]) }}" style="color: var(--accent); font-size: 13px; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-arrow-left"></i> Manajemen Akun</a>
    <h2 style="margin: 8px 0;">{{ $user->name }}</h2>
    <div style="color: var(--text-muted); font-size: 13px;">
        {{ $user->email ?? ($user->username ? '@'.$user->username : '') }} • <x-status-badge :model="$user" />
        <span class="badge {{ $user->status === 'aktif' ? 'badge-aktif' : 'badge-tolak' }}">{{ ucfirst($user->status) }}</span>
    </div>
</div>

@if (session('status'))
    <div class="alert-success" style="margin-bottom: 12px;">{{ session('status') }}</div>
@endif

{{-- 1. Statistik Kinerja (Paling Atas untuk Staf Admin & Korlap) --}}
@if (in_array($user->role, ['admin', 'korlap']))
    @php
        $totalProyek = $assignments->count();
        $selesai = $assignments->where('status_log', 'selesai')->count();
        $berjalan = $totalProyek - $selesai;
    @endphp
    <div class="card" style="margin-bottom: 16px;">
        <div style="font-weight: 600; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
            <i class="ti ti-chart-bar" style="color: var(--accent);"></i> Kinerja
        </div>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; font-size: 13px; text-align: center;">
            <div><div style="font-size: 22px; font-weight: 700; color: var(--accent);">{{ $totalProyek }}</div><div style="color: var(--text-muted);">Total Proyek</div></div>
            <div><div style="font-size: 22px; font-weight: 700; color: var(--accent);">{{ $selesai }}</div><div style="color: var(--text-muted);">Selesai</div></div>
            <div><div style="font-size: 22px; font-weight: 700;">{{ $berjalan }}</div><div style="color: var(--text-muted);">Berjalan</div></div>
        </div>
    </div>
@endif

{{-- 2. Section Tengah: Profil (Kiri) & Riwayat Proyek/Honor (Kanan) --}}
<div class="akun-grid-dua">
    {{-- Kolom Kiri: Profil Akun --}}
    <div style="display: flex; flex-direction: column; gap: 16px;">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <div style="font-weight: 600;">Profil Akun</div>
                @unless ($user->is_protected || $user->trashed())
                    <button type="button" class="btn btn-sm btn-tonal" onclick="document.getElementById('edit-user-dialog-{{ $user->id }}').showModal()" style="font-size: 12px; padding: 4px 10px;">
                        <i class="ti ti-edit"></i> Edit
                    </button>
                @endunless
            </div>
            <div class="table-container">
                <table style="width: 100%; font-size: 13px;">
                    <tr><td style="padding: 6px 0; width: 120px; color: var(--text-muted);">Nama</td><td style="font-weight: 500;">{{ $user->name }}</td></tr>
                    <tr><td style="padding: 6px 0; color: var(--text-muted);">Username</td><td>{{ $user->username ? '@'.$user->username : '-' }}</td></tr>
                    <tr><td style="padding: 6px 0; color: var(--text-muted);">Email</td><td>{{ $user->email ?? '-' }}</td></tr>
                    <tr><td style="padding: 6px 0; color: var(--text-muted);">WA</td><td>{{ $user->nomor_wa ? '+'.$user->nomor_wa : '-' }}</td></tr>
                    @if ($user->nama_perusahaan)
                        <tr><td style="padding: 6px 0; color: var(--text-muted);">Perusahaan/PH</td><td style="font-weight: 500;">{{ $user->nama_perusahaan }}</td></tr>
                    @endif
                    <tr><td style="padding: 6px 0; color: var(--text-muted);">Role</td><td><span class="badge badge-netral">{{ $user->label() }}</span></td></tr>
                    <tr><td style="padding: 6px 0; color: var(--text-muted);">Status</td><td><span class="badge {{ $user->status === 'aktif' ? 'badge-aktif' : 'badge-tolak' }}">{{ ucfirst($user->status) }}</span></td></tr>
                    @if (in_array($user->role, ['admin', 'korlap']))
                        <tr>
                            <td style="padding: 6px 0; color: var(--text-muted);">Honor/Event</td>
                            <td style="padding: 6px 0;">
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                                    <span style="font-weight: 600; color: var(--accent);">
                                         {{ $user->honor_nominal ? 'Rp ' . number_format($user->honor_nominal, 0, ',', '.') : 'Belum diatur' }}
                                    </span>
                                    <button type="button" class="btn btn-sm btn-tonal" style="padding: 2px 8px; font-size: 11.5px; height: auto;" onclick="document.getElementById('edit-user-dialog-{{ $user->id }}').showModal()" title="Edit Honor">
                                        <i class="ti ti-edit"></i> Ubah
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endif
                    <tr><td style="padding: 6px 0; color: var(--text-muted);">Bergabung</td><td>{{ $user->created_at->translatedFormat('d M Y') }}</td></tr>
                </table>
            </div>

            {{-- Aksi Cepat Akun (Disatukan ke card Profil) --}}
            @unless ($user->is_protected || $user->trashed())
                <div style="border-top: 1px solid var(--border-color); margin-top: 14px; padding-top: 14px; display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                    <button type="button" class="btn btn-sm btn-tonal" onclick="document.getElementById('toggle-dialog-{{ $user->id }}').showModal()">
                        <i class="ti {{ $user->status === 'aktif' ? 'ti-user-off' : 'ti-user-check' }}"></i> {{ $user->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                    <form method="POST" action="{{ route('super-admin.admins.reset-password', $user) }}" style="display:inline;" onsubmit="return confirm('Reset password akun ini? Password baru akan ditampilkan sekali.')">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-tonal"><i class="ti ti-key"></i> Reset Password</button>
                    </form>
                    <button type="button" class="btn btn-sm btn-danger-outline" onclick="document.getElementById('hapus-dialog-{{ $user->id }}').showModal()">
                        <i class="ti ti-trash"></i> Arsipkan
                    </button>
                </div>
            @endunless
        </div>
    </div>

    {{-- Kolom Kanan: Riwayat Proyek & Honor / Proyek Client / Portofolio Extras --}}
    <div style="display: flex; flex-direction: column; gap: 16px;">
        @if (! $user->isClient() && $user->role !== 'extras')
            <div class="card">
                <div style="font-weight: 600; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between;">
                    <span><i class="ti ti-folder" style="color: var(--accent);"></i> Riwayat Proyek &amp; Honor</span>
                    <span style="font-size: var(--fs-xs); color: var(--text-muted);">{{ $assignments->count() }} penugasan</span>
                </div>
                @if ($assignments->isEmpty())
                    <div style="color: var(--text-muted); text-align: center; padding: 20px; font-size: 13px;">Belum ada penugasan.</div>
                @else
                    <div class="table-container custom-scroll-box">
                        {{-- 1. Tabel Tampilan Desktop --}}
                        <table class="riwayat-table-desktop">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--border-color); position: sticky; top: 0; background: var(--bg-card); z-index: 2;">
                                    <th style="text-align: left; padding: 8px 0;">Proyek</th>
                                    <th style="text-align: left; padding: 8px 0;">Status</th>
                                    <th style="text-align: left; padding: 8px 0;">Mulai</th>
                                    <th style="text-align: left; padding: 8px 0;">Honor</th>
                                    <th style="text-align: left; padding: 8px 0;">Status Bayar</th>
                                    <th style="text-align: left; padding: 8px 0;">Slip</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($assignments as $a)
                                    <tr style="border-bottom: 1px solid var(--border-color);">
                                        <td style="padding: 8px 0;"><a href="{{ route('admin.projects.applicants', $a->castingProject) }}" style="color: var(--accent); font-weight: 500;">{{ $a->castingProject->nama_produksi }}</a></td>
                                        <td style="padding: 8px 0;"><span class="badge {{ $a->status_log === 'berjalan' ? 'badge-info' : 'badge-aktif' }}">{{ ucfirst($a->status_log) }}</span></td>
                                        <td style="padding: 8px 0; color: var(--text-muted);">{{ $a->created_at->translatedFormat('d M') }}</td>
                                        <td style="padding: 8px 0; font-weight: 600;">@if ($a->payroll) Rp {{ number_format($a->payroll->nominalTotal(), 0, ',', '.') }} @else - @endif</td>
                                        <td style="padding: 8px 0;">@if ($a->payroll) <span class="badge {{ $a->payroll->isDibayar() ? 'badge-aktif' : 'badge-netral' }}">{{ $a->payroll->isDibayar() ? 'Dibayar' : 'Belum diproses' }}</span> @else - @endif</td>
                                        <td style="padding: 8px 0;">
                                            @if ($a->payroll?->pdf_slip_path)
                                                <div style="display: inline-flex; gap: 4px;">
                                                    <a href="{{ route('admin.payrolls.slip', [$a->payroll, 'preview' => 1]) }}" target="_blank" class="btn btn-sm btn-tonal" title="Pratinjau Slip"><i class="ti ti-eye"></i></a>
                                                    <a href="{{ route('admin.payrolls.slip', $a->payroll) }}" class="btn btn-sm" title="Unduh Slip"><i class="ti ti-download"></i></a>
                                                </div>
                                            @else - @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        {{-- 2. Kartu Tampilan Mobile (Responsif & Rapi) --}}
                        <div class="riwayat-cards-mobile">
                            @foreach ($assignments as $a)
                                <div style="background: var(--bg-card-hover, rgba(255,255,255,0.03)); border: 1px solid var(--border-color); border-radius: var(--radius-sm, 8px); padding: 10px 12px;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 6px;">
                                        <a href="{{ route('admin.projects.applicants', $a->castingProject) }}" style="color: var(--accent); font-weight: 600; font-size: 13.5px; text-decoration: none;">
                                            {{ $a->castingProject->nama_produksi }}
                                        </a>
                                        <span class="badge {{ $a->status_log === 'berjalan' ? 'badge-info' : 'badge-aktif' }}" style="font-size: 11px;">
                                            {{ ucfirst($a->status_log) }}
                                        </span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: var(--text-muted); margin-bottom: 6px;">
                                        <span>Mulai: {{ $a->created_at->translatedFormat('d M Y') }}</span>
                                        <span style="font-weight: 600; color: var(--text-color);">
                                            @if ($a->payroll) Rp {{ number_format($a->payroll->nominalTotal(), 0, ',', '.') }} @else - @endif
                                        </span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed var(--border-color); padding-top: 6px; font-size: 11.5px;">
                                        <div>
                                            Status: 
                                            @if ($a->payroll)
                                                <span class="badge {{ $a->payroll->isDibayar() ? 'badge-aktif' : 'badge-netral' }}" style="font-size: 10.5px; padding: 1px 6px;">
                                                    {{ $a->payroll->isDibayar() ? 'Dibayar' : 'Belum diproses' }}
                                                </span>
                                            @else
                                                <span style="color: var(--text-muted);">-</span>
                                            @endif
                                        </div>
                                        @if ($a->payroll?->pdf_slip_path)
                                            <div style="display: flex; gap: 4px;">
                                                <a href="{{ route('admin.payrolls.slip', [$a->payroll, 'preview' => 1]) }}" target="_blank" class="btn btn-sm btn-tonal" style="padding: 2px 6px; font-size: 11px;" title="Pratinjau Slip"><i class="ti ti-eye"></i> Pratinjau</a>
                                                <a href="{{ route('admin.payrolls.slip', $a->payroll) }}" class="btn btn-sm" style="padding: 2px 6px; font-size: 11px;" title="Unduh Slip"><i class="ti ti-download"></i></a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Riwayat Reimbursement --}}
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
                <div class="card">
                    <div style="font-weight: 600; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                        <i class="ti ti-receipt" style="color: var(--accent);"></i> Riwayat Reimbursement &amp; Biaya Tambahan
                    </div>
                    <div class="table-container custom-scroll-box" style="max-height: 200px;">
                        <table style="width: 100%; font-size: 13px;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--border-color); position: sticky; top: 0; background: var(--bg-card); z-index: 2;">
                                    <th style="text-align: left; padding: 6px 0;">Tanggal</th>
                                    <th style="text-align: left; padding: 6px 0;">Proyek</th>
                                    <th style="text-align: left; padding: 6px 0;">Keperluan</th>
                                    <th style="text-align: right; padding: 6px 0;">Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($staffReimbursements as $sr)
                                    <tr style="border-bottom: 1px solid var(--border-color);">
                                        <td style="padding: 6px 0; color: var(--text-muted); font-size: 12px;">{{ $sr->tanggal ? $sr->tanggal->translatedFormat('d M Y') : '-' }}</td>
                                        <td style="padding: 6px 0;">{{ $sr->proyek }}</td>
                                        <td style="padding: 6px 0; font-weight: 500;">{{ $sr->label }}</td>
                                        <td style="padding: 6px 0; text-align: right; font-weight: 600; color: var(--accent-strong);">
                                            Rp {{ number_format($sr->nominal, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @elseif ($user->isClient() && $clientProjects)
            <div class="card">
                <div style="font-weight: 600; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                    <i class="ti ti-briefcase" style="color: var(--accent);"></i> Proyek Client
                </div>
                @if ($clientProjects->isEmpty())
                    <div style="color: var(--text-muted); text-align: center; padding: 20px; font-size: 13px;">Belum ada proyek.</div>
                @else
                    <div class="table-container custom-scroll-box">
                        <table style="width: 100%; font-size: 13px;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--border-color); position: sticky; top: 0; background: var(--bg-card); z-index: 2;">
                                    <th style="text-align: left; padding: 6px 0;">Nama Produksi</th>
                                    <th style="text-align: left; padding: 6px 0;">Status</th>
                                    <th style="text-align: left; padding: 6px 0;">Deadline</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($clientProjects as $p)
                                    <tr style="border-bottom: 1px solid var(--border-color);">
                                        <td style="padding: 6px 0;"><a href="{{ route('admin.projects.show', $p) }}" style="color: var(--accent); font-weight: 500;">{{ $p->nama_produksi }}</a></td>
                                        <td style="padding: 6px 0;"><x-status-badge :model="$p" /></td>
                                        <td style="padding: 6px 0; color: var(--text-muted);">{{ $p->deadline?->translatedFormat('d M Y') ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @elseif ($user->role === 'extras')
            <div class="card">
                <div style="font-weight: 600; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                    <span style="display: flex; align-items: center; gap: 6px;">
                        <i class="ti ti-star" style="color: var(--accent);"></i> Penilaian &amp; Profil Talent
                    </span>
                    <div style="display: flex; gap: 6px; align-items: center;">
                        <a href="{{ route('admin.extras.profil', $user) }}" class="btn btn-sm btn-brand" target="_blank" title="Buka Halaman Portofolio Casting">
                            <i class="ti ti-id"></i> Lihat Profil Lengkap Extras <i class="ti ti-external-link"></i>
                        </a>
                        @if ($user->extrasProfile)
                            @include('partials.toggle-beranda', ['profile' => $user->extrasProfile])
                        @endif
                    </div>
                </div>

                @if ($user->extrasProfile)
                    {{-- Form Langsung Set Grade & Tag Extras --}}
                    <form method="POST" action="{{ route('super-admin.admins.extras-update', $user) }}" style="background: var(--bg-card-hover, rgba(255,255,255,0.02)); border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); padding: 14px; margin-bottom: 16px;">
                        @csrf @method('PATCH')
                        
                        <div style="margin-bottom: 12px;">
                            <label style="font-size: var(--fs-sm); font-weight: 600; display: block; margin-bottom: 4px;">
                                Grade Extras Saat Ini:
                                @if ($user->extrasProfile->grade_saat_ini)
                                    <span class="badge badge-aktif" style="font-weight: 700; margin-left: 4px;">Grade {{ $user->extrasProfile->grade_saat_ini }}</span>
                                @else
                                    <span class="badge badge-netral" style="margin-left: 4px;">Belum dinilai</span>
                                @endif
                            </label>
                            <select name="grade" style="width: 100%;">
                                <option value="belum" @selected(! $user->extrasProfile->grade_saat_ini)>Belum dinilai</option>
                                <option value="A" @selected($user->extrasProfile->grade_saat_ini === 'A')>Grade A (Utama / Profesional)</option>
                                <option value="B" @selected($user->extrasProfile->grade_saat_ini === 'B')>Grade B (Standar / Berpengalaman)</option>
                                <option value="C" @selected($user->extrasProfile->grade_saat_ini === 'C')>Grade C (Pemula / Baru)</option>
                            </select>
                        </div>

                        <div style="margin-bottom: 14px;">
                            <label style="font-size: var(--fs-sm); font-weight: 600; display: block; margin-bottom: 4px;">Tag / Kategori Look</label>
                            @include('partials.tag-input', ['name' => 'tag_nama', 'selected' => $user->extrasProfile->categories, 'tagGroups' => $availableKategori])
                        </div>

                        <button type="submit" class="btn btn-sm btn-brand">
                            <i class="ti ti-check"></i> Simpan Grade &amp; Tag
                        </button>
                    </form>

                    <div style="font-weight: 600; margin: 12px 0 8px; display: flex; align-items: center; justify-content: space-between;">
                        <span>Riwayat Pendaftaran Proyek</span>
                        <span style="font-size: var(--fs-xs); color: var(--text-muted);">{{ $user->extrasProfile->applications->count() }} proyek</span>
                    </div>
                    <div class="custom-scroll-box" style="max-height: 220px;">
                        @forelse ($user->extrasProfile->applications->sortByDesc('id') as $app)
                            <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 8px 0; border-top: 1px solid var(--border-color); font-size: 13px;">
                                <span style="overflow-wrap: anywhere; font-weight: 500;">{{ $app->castingProject?->nama_produksi ?? '-' }}</span>
                                <span class="badge {{ $app->badgeClass() }}">{{ $app->label() }}</span>
                            </div>
                        @empty
                            <div style="color: var(--text-muted); font-size: 13px; padding: 8px 0;">Belum pernah daftar proyek.</div>
                        @endforelse
                    </div>
                @else
                    <div style="color: var(--text-muted); font-size: 13px;">Profil talent belum diisi oleh extras ini.</div>
                @endif
            </div>
        @endif
    </div>
</div>

{{-- 3. Section Bawah: Notifikasi Terakhir (Kiri) & Audit Trail Aktivitas (Kanan) --}}
<div class="akun-grid-dua">
    {{-- Kolom Kiri: Notifikasi Terakhir --}}
    <div class="card">
        <div style="font-weight: 600; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between;">
            <span style="display: flex; align-items: center; gap: 6px;"><i class="ti ti-bell" style="color: var(--accent);"></i> Notifikasi Terakhir</span>
            <span style="font-size: var(--fs-xs); color: var(--text-muted);">{{ $notifikasi->count() }} notifikasi</span>
        </div>
        @if ($notifikasi->isEmpty())
            <div style="color: var(--text-muted); text-align: center; padding: 20px; font-size: 13px;">Belum ada notifikasi.</div>
        @else
            <div class="table-container custom-scroll-box">
                <table style="width: 100%; font-size: 12.5px; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); position: sticky; top: 0; background: var(--bg-card); z-index: 2;">
                            <th style="text-align: left; padding: 6px 8px 6px 0; width: 105px; white-space: nowrap; font-size: 12px;">Waktu</th>
                            <th style="text-align: left; padding: 6px 8px; font-size: 12px;">Notifikasi</th>
                            <th style="text-align: center; padding: 6px 0; width: 60px; font-size: 12px;">Kirim</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($notifikasi as $n)
                            @php
                                $kanal = collect(['wa' => ['ti-brand-whatsapp', 'WA'], 'email' => ['ti-mail', 'Email']])
                                    ->filter(fn ($_, $k) => array_key_exists($k, $n->data));
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 6px 8px 6px 0; color: var(--text-muted); font-size: 11.5px; white-space: nowrap; vertical-align: top;">{{ $n->created_at->translatedFormat('d M H:i') }}</td>
                                <td style="padding: 6px 8px; vertical-align: top;">
                                    <strong style="font-size: 12.5px; display: block; line-height: 1.3;">{{ $n->data['judul'] ?? '-' }}</strong>
                                    <div style="color: var(--text-muted); font-size: 11.5px; line-height: 1.35; margin-top: 2px;">{{ $n->data['pesan'] ?? '' }}</div>
                                </td>
                                <td style="padding: 6px 0; white-space: nowrap; text-align: center; vertical-align: top;">
                                    @forelse ($kanal as $k => [$ikon, $nama])
                                        @php $st = $n->data[$k]; @endphp
                                        <span data-status-{{ $k }}="{{ $st ?? 'antre' }}" title="{{ $nama }}: {{ $st ?? 'antre' }}{{ $k === 'wa' && ! empty($n->data['wa_dikirim_at']) ? ' ('.$n->data['wa_dikirim_at'].')' : '' }}"
                                              style="color: {{ $st === 'terkirim' ? 'var(--accent)' : ($st === 'gagal' ? 'var(--danger)' : 'var(--text-muted)') }}; font-size: 13px;">
                                            <i class="ti {{ $ikon }}" aria-hidden="true"></i><i class="ti {{ $st === 'terkirim' ? 'ti-check' : ($st === 'gagal' ? 'ti-x' : 'ti-clock') }}" aria-hidden="true"></i>
                                        </span>
                                    @empty
                                        <span style="color: var(--text-muted); font-size: 11px;">-</span>
                                    @endforelse
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Kolom Kanan: Aktivitas Akun Ini (Audit Trail) --}}
    <div class="card">
        <div style="font-weight: 600; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between;">
            <span style="display: flex; align-items: center; gap: 6px;"><i class="ti ti-activity" style="color: var(--accent);"></i> Aktivitas Akun Ini (Audit Trail)</span>
            <span style="font-size: var(--fs-xs); color: var(--text-muted);">{{ $userActivities->count() }} aktivitas</span>
        </div>
        @if ($userActivities->isEmpty())
            <div style="color: var(--text-muted); text-align: center; padding: 20px; font-size: 13px;">Belum ada aktivitas.</div>
        @else
            <div class="table-container custom-scroll-box">
                <table style="width: 100%; font-size: 12.5px; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); position: sticky; top: 0; background: var(--bg-card); z-index: 2;">
                            <th style="text-align: left; padding: 6px 8px 6px 0; width: 105px; white-space: nowrap; font-size: 12px;">Waktu</th>
                            <th style="text-align: left; padding: 6px 8px; width: 130px; white-space: nowrap; font-size: 12px;">Aktivitas</th>
                            <th style="text-align: left; padding: 6px 0; font-size: 12px;">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($userActivities as $act)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 6px 8px 6px 0; color: var(--text-muted); font-size: 11.5px; white-space: nowrap; vertical-align: top;">{{ $act->created_at->translatedFormat('d M H:i') }}</td>
                                <td style="padding: 6px 8px; vertical-align: top;">
                                    <span class="badge badge-netral" title="{{ $act->action }}" style="font-size: 11px; padding: 2px 6px; white-space: normal; line-height: 1.2;">
                                        {{ \App\Models\ActivityLog::actionLabel($act->action) }}
                                    </span>
                                </td>
                                <td style="padding: 6px 0; font-size: 12px; line-height: 1.35; vertical-align: top; color: var(--text-color);">
                                    {{ $act->description }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@include('super-admin.akun.aksi-dialogs', ['u' => $user])
@endsection
