@extends('layouts.app')

@section('title', 'Dashboard Super Admin')

@php
    $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
@endphp

@push('styles')
<style>
.sa-head-judul { font-size: 18px; font-weight: 700; margin: 0 0 16px; }
.sa-lihat-semua { color: var(--accent); font-weight: 600; display: inline-block; margin-top: 10px; font-size: 12.5px; }
.sa-card + .sa-card { margin-top: 16px; }
.sa-card + .dashboard-grid-2col { margin-top: 16px; }
.dashboard-grid-2col .sa-card + .sa-card { margin-top: 0; }
</style>
@endpush

@section('content')
<h1 class="sa-head-judul">Dashboard</h1>

<div class="kpi-grid">
    <x-kpi label="Menunggu keputusan" :angka="$menungguKeputusan" :href="route('super-admin.persetujuan')" :mendesak="$menungguKeputusan > 0 && $pengajuanMendesak" />
    <x-kpi label="Tugas Admin tertunda" :angka="$pantauTotal" :sub="'di '.$pantauAdmin->count().' Admin'" :href="route('super-admin.pantau-admin.index')" :mendesak="$pantauMendesak" />
    <x-kpi label="Proyek berjalan" :angka="$proyekBerjalan" :pertumbuhan="$pertumbuhanBerjalan" :href="route('admin.projects.index', ['tahap' => 'berjalan', 'dari' => $dari->format('Y-m-d'), 'sampai' => $sampai->format('Y-m-d')])" />
    <x-kpi label="Invoice belum lunas" :angka="$invoiceBelumLunas->count()" :sub="$rp($invoiceTotal)" :href="route('admin.projects.index')" :mendesak="$invoiceBelumLunas->isNotEmpty()" />
</div>

<div class="card sa-card">
    <div class="card-title">Jadwal</div>
    <x-navigasi-timeline :dari="$dariJadwal" :sampai="$sampaiJadwal" :skala="$skala" :action="route('super-admin.dashboard')" />
    <x-agenda :events="$agendaEvents" />
    <a href="{{ route('admin.projects.index') }}" class="sa-lihat-semua">Lihat semua &rarr;</a>
</div>

<div class="dashboard-grid-2col is-even">
    <div class="card sa-card">
        <div class="card-title">Butuh keputusan Anda</div>
        @forelse ($butuhKeputusan as $item)
            @include('super-admin.dashboard._butuh-keputusan', ['item' => $item])
        @empty
            <x-kosong ikon="circle-check" teks="Tidak ada yang perlu diputuskan." />
        @endforelse
        @if ($jumlahTindakan)
            <a href="{{ route('super-admin.persetujuan') }}" class="sa-lihat-semua">Lihat semua ({{ $jumlahTindakan }}) &rarr;</a>
        @endif
    </div>

    <div class="card sa-card">
        <div class="card-title">Admin perlu diingatkan</div>
        @forelse ($diingatkan as $t)
            @include('super-admin.dashboard._diingatkan', ['t' => $t])
        @empty
            <x-kosong teks="Semua Admin sesuai jadwal." />
        @endforelse
        @if ($pantauTotal)
            <a href="{{ route('super-admin.pantau-admin.index') }}" class="sa-lihat-semua">Lihat semua &rarr;</a>
        @endif
    </div>
</div>
@endsection
