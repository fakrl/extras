@extends('layouts.app')

@section('title', 'Monitoring Akun')

@section('content')
<div class="card-header-row" style="margin-bottom: 20px;">
    <div>
        <div style="font-size: 18px; font-weight: 700;">Monitoring Akun</div>
        <p style="font-size: 13px; color: var(--text-secondary); margin: 4px 0 0;">
            Monitoring akun dan absensi sistem. Aksi cepat tersedia langsung dari hasil pencarian.
        </p>
    </div>
    <a href="{{ route('super-admin.admins.index') }}" class="btn btn-sm btn-brand">Kelola Akun &rarr;</a>
</div>

{{-- Jadwal Shooting + Penugasan Admin sejajar --}}
<div class="dashboard-grid-2col is-even" style="margin-bottom: 20px;">
    <div class="card">
        <div class="card-title"><i class="ti ti-calendar-event" style="color: var(--accent);"></i> Jadwal Shooting</div>
        <x-jadwal-calendar :events="$shootingDates" compact />
    </div>

    <div class="card">
        <div class="card-title">Penugasan Admin Selesai</div>
        @php $pct = $assignmentTotal > 0 ? round($assignmentSelesai / $assignmentTotal * 100) : 0; @endphp
        <div style="font-size: 28px; font-weight: 700; color: var(--accent); margin: 10px 0 4px;">{{ $pct }}%</div>
        <div style="font-size: 12.5px; color: var(--text-muted); margin-bottom: 14px;">{{ $assignmentSelesai }} dari {{ $assignmentTotal }} penugasan selesai</div>
        <div style="background: var(--bg-nav-active); border-radius: 20px; height: 10px; overflow: hidden;">
            <div style="background: var(--accent); height: 100%; width: {{ $pct }}%;"></div>
        </div>
    </div>
</div>

{{-- Stat Cards --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 20px;">
    <div class="metric-card" style="text-align: center;">
        <div class="metric-value">{{ $extrasAktif }}<span style="font-size: 14px; font-weight: 400; color: var(--text-muted);">/{{ $extrasTotal }}</span></div>
        <div class="metric-label">Extras Aktif</div>
    </div>
    <div class="metric-card" style="text-align: center;">
        <div class="metric-value">{{ $cdTotal }}</div>
        <div class="metric-label">Client / PH</div>
    </div>
    <div class="metric-card" style="text-align: center;">
        <div class="metric-value">{{ $adminTotal }}</div>
        <div class="metric-label">Admin & Korlap</div>
    </div>
    <div class="metric-card" style="text-align: center;">
        <div class="metric-value">{{ $attendanceStats['total_hadir'] }}</div>
        <div class="metric-label">Total Hadir</div>
    </div>
    <div class="metric-card" style="text-align: center;">
        <div class="metric-value" style="{{ $attendanceStats['menunggu_validasi'] > 0 ? 'color: var(--warning, #e67e22);' : '' }}">{{ $attendanceStats['menunggu_validasi'] }}</div>
        <div class="metric-label">Menunggu Validasi</div>
    </div>
</div>

{{-- Absensi Lapangan --}}
<div class="card" style="margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
        <div class="card-title" style="margin: 0;"><i class="ti ti-camera"></i> Absensi Lapangan</div>
        <a href="{{ route('super-admin.attendance.index') }}" class="btn btn-sm btn-brand">Absensi Lapangan &rarr;</a>
    </div>

    @forelse ($absensiPerProyek as $namaProyek => $attendances)
        <details style="margin-bottom: 8px; border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden;">
            <summary style="padding: 10px 14px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; background: var(--bg-card); font-weight: 600; font-size: 13.5px; list-style: none;">
                <span><i class="ti ti-movie" style="color: var(--accent); margin-right: 6px;"></i> {{ $namaProyek }}</span>
                <span class="badge badge-pending" style="font-size: var(--fs-xs);">{{ $attendances->count() }} absensi</span>
            </summary>
            <div style="overflow-x: auto; padding: 0 4px 4px;">
                <div class="table-container">
                <table style="font-size: 12.5px;">
                    <thead>
                        <tr>
                            <th>Waktu</th><th>Extras</th><th>Tgl Shooting</th><th>Status</th><th>Validasi</th><th>Foto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($attendances as $att)
                            <tr>
                                <td style="white-space: nowrap; color: var(--text-muted);">{{ $att->created_at->format('d/m H:i') }}</td>
                                <td><strong>{{ $att->projectApplication?->extras?->user?->username ?? $att->projectApplication?->extras?->user?->name ?? '-' }}</strong></td>
                                <td style="white-space: nowrap;">{{ $att->eventShootingDate?->tanggal?->format('d M Y') ?? '-' }}</td>
                                <td><span class="badge {{ $att->status === 'hadir' ? 'badge-aktif' : 'badge-tolak' }}">{{ $att->status === 'hadir' ? 'Hadir' : 'Tidak' }}</span></td>
                                <td>
                                    @if ($att->status_validasi === 'tervalidasi')
                                        <span class="badge badge-aktif">Tervalidasi</span>
                                    @else
                                        <span class="badge badge-pending">Menunggu</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($att->foto_path)
                                        <a href="{{ route('admin.absensi.foto', $att) }}" target="_blank" class="btn btn-sm" style="font-size: var(--fs-xs); padding: 2px 6px;"><i class="ti ti-photo"></i></a>
                                    @else <span style="color: var(--text-muted);">-</span> @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>
        </details>
    @empty
        <p style="color: var(--text-muted); font-size: 13px; text-align: center; padding: 20px 0;">Belum ada riwayat absensi lapangan.</p>
    @endforelse
</div>

{{-- Unified Search --}}
<div class="card" style="margin-bottom: 20px;">
    <div style="font-weight: 600; font-size: 14.5px; margin-bottom: 12px;">Cari Pengguna</div>

    <div style="display: flex; gap: 6px; margin-bottom: 10px; flex-wrap: wrap;">
        @foreach (['all' => 'Semua', 'extras' => 'Extras', 'client' => 'Client', 'admin' => 'Admin/Korlap'] as $val => $label)
            <a href="{{ request()->fullUrlWithQuery(['type' => $val, 'q' => $unifiedSearch]) }}"
               class="btn btn-sm {{ $unifiedType === $val ? 'btn-brand' : '' }}" style="border-radius: 20px; font-size: 12px;">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <form method="GET" style="display: flex; gap: 8px; margin-bottom: 12px;">
        <input type="hidden" name="type" value="{{ $unifiedType }}">
        <div style="position: relative; flex: 1;">
            <input type="text" name="q" value="{{ $unifiedSearch }}" placeholder="Cari nama, email, atau alias..."
                   style="width: 100%; padding: 8px 12px 8px 34px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-card); color: var(--text-primary); font-size: var(--fs-md); margin: 0;"
                   id="unified-search-input">
            <i class="ti ti-search" style="position: absolute; left: 11px; top: 11px; color: var(--text-muted);"></i>
        </div>
        @if ($unifiedSearch)
            <a href="{{ request()->fullUrlWithQuery(['q' => '', 'type' => $unifiedType]) }}" class="btn btn-sm">Reset</a>
        @endif
    </form>

    <div style="max-height: 380px; overflow-y: auto;">
        @forelse ($unifiedResults as $person)
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--border-color);">
                <div>
                    <div style="font-weight: 600; font-size: 13.5px;">
                        {{ $person->name }}
                        @if ($person->username) <span style="font-size: 12px; color: var(--text-muted);">{{ '@'.$person->username }}</span> @endif
                    </div>
                    <div style="font-size: 12px; color: var(--text-muted);">{{ $person->email }} &bull; <x-status-badge :model="$person" style="font-size: var(--fs-xs);" /></div>
                </div>
                <details class="kebab-menu" style="position: relative;">
                    <summary style="list-style: none; cursor: pointer; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-card);">
                        <i class="ti ti-dots-vertical"></i>
                    </summary>
                    <div style="position: absolute; right: 0; top: 34px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; min-width: 160px; box-shadow: 0 4px 16px rgba(0,0,0,0.15); padding: 4px 0; z-index: 40;">
                        @if ($person->role === 'extras')
                            <a href="{{ route('admin.extras.profil', $person) }}" style="display:flex;align-items:center;gap:8px;padding:8px 14px;font-size:13px;color:var(--text-primary);text-decoration:none;">
                                <i class="ti ti-user"></i> Lihat Profil
                            </a>
                        @else
                            <a href="{{ route('super-admin.admins.show', $person) }}" style="display:flex;align-items:center;gap:8px;padding:8px 14px;font-size:13px;color:var(--text-primary);text-decoration:none;">
                                <i class="ti ti-id"></i> Lihat Detail
                            </a>
                        @endif
                        @if (! $person->is_protected && $person->id !== auth()->id())
                            <button type="button" onclick="document.getElementById('edit-person-{{ $person->id }}').showModal()" style="display:flex;align-items:center;gap:8px;width:100%;padding:8px 14px;font-size:13px;text-align:left;background:none;border:none;color:var(--text-primary);cursor:pointer;">
                                <i class="ti ti-edit"></i> Edit
                            </button>
                            <form method="POST" action="{{ route('super-admin.admins.reset-password', $person) }}">
                                @csrf
                                <button type="submit" onclick="return confirm('Reset password {{ $person->name }}?')" style="display:flex;align-items:center;gap:8px;width:100%;padding:8px 14px;font-size:13px;text-align:left;background:none;border:none;color:var(--text-primary);cursor:pointer;">
                                    <i class="ti ti-key"></i> Reset Password
                                </button>
                            </form>
                            @if ($person->status === 'aktif')
                                <button type="button" onclick="document.getElementById('toggle-person-{{ $person->id }}').showModal()" style="display:flex;align-items:center;gap:8px;width:100%;padding:8px 14px;font-size:13px;text-align:left;background:none;border:none;color:var(--danger);cursor:pointer;">
                                    <i class="ti ti-ban"></i> Nonaktifkan
                                </button>
                            @else
                                <button type="button" onclick="document.getElementById('toggle-person-{{ $person->id }}').showModal()" style="display:flex;align-items:center;gap:8px;width:100%;padding:8px 14px;font-size:13px;text-align:left;background:none;border:none;color:var(--accent-strong);cursor:pointer;">
                                    <i class="ti ti-check"></i> Aktifkan
                                </button>
                            @endif
                        @endif
                    </div>
                </details>
            </div>

            @if (! $person->is_protected && $person->id !== auth()->id())
                <dialog id="edit-person-{{ $person->id }}" style="border:1px solid var(--border-color);border-radius:10px;padding:0;max-width:420px;width:90%;">
                    <div style="padding:18px;">
                        <div style="font-size:15px;font-weight:600;margin-bottom:12px;">Edit -- {{ $person->name }}</div>
                        <form method="POST" action="{{ route('super-admin.admins.update', $person) }}">
                            @csrf @method('PATCH')
                            <label>Nama</label>
                            <input type="text" name="name" value="{{ $person->name }}" required style="width:100%;margin-bottom:10px;">
                            <label>Email</label>
                            <input type="email" name="email" value="{{ $person->email }}" @required(! $person->isClient()) style="width:100%;margin-bottom:10px;">
                            <label>Role</label>
                            <select name="role" required style="width:100%;margin-bottom:14px;">
                                <option value="admin" @selected($person->role==='admin')>Admin</option>
                                <option value="korlap" @selected($person->role==='korlap')>Korlap</option>
                                <option value="client" @selected($person->role==='client')>Client</option>
                                <option value="extras" @selected($person->role==='extras')>Extras</option>
                                @if (auth()->user()->is_protected)
                                    <option value="super_admin" @selected($person->role==='super_admin')>Super Admin</option>
                                @endif
                            </select>
                            <div style="display:flex;gap:8px;justify-content:flex-end;">
                                <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                                <button type="submit" class="btn btn-sm btn-brand">Simpan</button>
                            </div>
                        </form>
                    </div>
                </dialog>
                <dialog id="toggle-person-{{ $person->id }}" style="border:1px solid var(--border-color);border-radius:10px;padding:0;max-width:340px;width:90%;">
                    <form method="POST" action="{{ route('super-admin.admins.toggle-status', $person) }}" style="padding:18px;">
                        @csrf @method('PATCH')
                        <div style="font-weight:600;margin-bottom:10px;">{{ $person->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }} {{ $person->name }}?</div>
                        <div style="display:flex;gap:8px;justify-content:flex-end;">
                            <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                            <button type="submit" class="btn btn-sm btn-brand">Ya</button>
                        </div>
                    </form>
                </dialog>
            @endif
        @empty
            <p style="color: var(--text-muted); font-size: 13px; text-align: center; padding: 20px 0;">
                {{ $unifiedSearch ? 'Tidak ada hasil untuk "'.$unifiedSearch.'".' : 'Tidak ada pengguna.' }}
            </p>
        @endforelse
    </div>
    @if (count($unifiedResults) >= 30)
        <p style="font-size:12px;color:var(--text-muted);margin-top:8px;text-align:center;">Menampilkan 30 hasil teratas. Persempit pencarian untuk hasil lebih spesifik.</p>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    var input = document.getElementById('unified-search-input');
    if (!input) return;
    var timer;
    input.addEventListener('input', function () {
        clearTimeout(timer);
        var form = this.form;
        timer = setTimeout(function () { form.submit(); }, 400);
    });
}());
</script>
@endpush
