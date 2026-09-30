@extends('layouts.app')

@section('title', 'Monitoring · Admin')

@include('super-admin.monitoring._gaya')

@section('content')
@include('super-admin.monitoring._kepala', ['mode' => 'admin', 'teks' => 'Pratinjau pekerjaan Admin, lihat saja. Klik kartu untuk masuk mode Admin langsung ke halaman terkait.'])

<div class="mon-kartu">
    @foreach ($ringkasan as $key => $r)
        <button type="submit" form="mon-masuk" name="ke" value="{{ $r['url'] }}" class="metric-card mon-btn {{ $r['jumlah'] ? 'is-ada' : '' }}" data-kartu="{{ $key }}">
            <div class="metric-label">{{ $r['label'] }}</div>
            <div class="metric-value">{{ $r['jumlah'] }}</div>
        </button>
    @endforeach
</div>

<div class="card" style="margin-bottom: 16px;">
    <div class="card-title">Tahapan Partisipasi Kandidat</div>
    @include('admin.partials.tahapan', ['monitor' => true])
</div>

<div class="mon-dua">
    <div class="card">
        <div class="card-title">Per Admin</div>
        <div class="table-container">
            <table>
                <thead><tr><th>Nama</th><th>Proyek PIC aktif</th><th>Menunggu dia</th><th>Aksi terakhir</th></tr></thead>
                <tbody>
                    @forelse ($admins as $a)
                        <tr>
                            <td><a href="{{ route('super-admin.admins.show', $a) }}" style="font-weight: 600;">{{ $a->name }}</a></td>
                            <td>{{ $a->proyek_aktif }}</td>
                            <td>@if ($a->menunggu)<span class="badge badge-pending">{{ $a->menunggu }}</span>@else<span class="dash-sub">0</span>@endif</td>
                            <td>
                                @if ($a->aktivitasTerakhir)
                                    {{ \App\Models\ActivityLog::actionLabel($a->aktivitasTerakhir->action) }}
                                    <div class="dash-sub">{{ $a->aktivitasTerakhir->created_at?->diffForHumans() }}</div>
                                @else
                                    <span class="dash-sub">Belum ada</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="dash-sub" style="text-align: center;">Belum ada Admin aktif.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="dash-sub" style="margin: 8px 0 0;">Menunggu dia = nego menunggu balasan + kontrak menunggu TTD di proyek yang dia PIC-i.</p>
    </div>

    <div class="card">
        <div class="card-title">Proyek berjalan</div>
        @forelse ($proyek as $p)
            @php $tahap = $p->tahap(); @endphp
            <a href="{{ route('admin.projects.show', $p) }}" class="dash-row" style="flex-wrap: nowrap;">
                <div style="min-width: 0;">
                    <strong>{{ $p->nama_produksi }}</strong>
                    @if ($tahap)
                        <span class="badge {{ \App\Models\CastingProject::TAHAP_BADGES[$tahap] }}">{{ \App\Models\CastingProject::TAHAP[$tahap] }}</span>
                    @endif
                    <div class="dash-sub">PIC {{ $p->admin?->name ?? '-' }} &bull; {{ $p->rentangShooting() }}</div>
                    <div class="dash-sub">{{ $p->terisi }} terisi &middot; {{ $p->deal }} deal &middot; {{ $p->lolos }} lolos</div>
                </div>
                <div style="text-align: right; flex-shrink: 0;">
                    <strong>{{ $p->applications_count }}/{{ $p->kuota }}</strong>
                    <div class="dash-sub">pendaftar/kuota</div>
                </div>
            </a>
        @empty
            <div class="dash-aman" role="status"><i class="ti ti-circle-check"></i> Tidak ada proyek berjalan atau mendatang.</div>
        @endforelse
    </div>
</div>
@endsection
