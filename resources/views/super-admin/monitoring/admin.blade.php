@extends('layouts.app')

@section('title', 'Monitoring · Admin')

@include('super-admin.monitoring._gaya')

@section('content')
@include('super-admin.monitoring._kepala', ['mode' => 'admin', 'teks' => 'Pratinjau pekerjaan Admin, lihat saja. Klik kartu untuk masuk mode Admin langsung ke halaman terkait. Di mode Admin, aksi yang Anda lakukan nyata dan tercatat atas nama Anda.'])

<div class="card" style="margin-bottom: 16px;">
    <div class="card-title">Tahapan Partisipasi Kandidat</div>
    @include('admin.partials.tahapan', ['monitor' => true])
</div>

<div class="mon-dua">
    <div class="card">
        <div class="card-title">Daftar Admin</div>
        @forelse ($admins as $a)
            @include('super-admin.monitoring._orang', ['u' => $a, 'label' => 'Admin'])
        @empty
            <x-kosong teks="Belum ada Admin aktif." />
        @endforelse
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
