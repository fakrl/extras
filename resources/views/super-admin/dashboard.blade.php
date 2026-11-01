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
    <x-kpi label="Menunggu keputusan" :angka="$menungguKeputusan" :href="route('admin.projects.index', ['tahap' => 'menunggu_acc'])" :mendesak="$menungguKeputusan > 0 && $pengajuanMendesak" :accent="'#D97706'" />
    <x-kpi label="Tugas staf tertunda" :angka="$stafTertunda" :sub="'di '.($pantauAdmin->count() + $tugasKorlap->count()).' staf'" :href="route('super-admin.monitoring.admin')" :mendesak="$stafMendesak" :accent="'#DC2626'" />
    <x-kpi label="Proyek berjalan" :angka="$proyekBerjalan" :pertumbuhan="$pertumbuhanBerjalan" :href="route('admin.projects.index', ['tahap' => 'berjalan', 'dari' => $dari->format('Y-m-d'), 'sampai' => $sampai->format('Y-m-d')])" :accent="'#15803D'" />
    <x-kpi label="Invoice belum lunas" :angka="$invoiceBelumLunas->count()" :sub="$rp($invoiceTotal)" :href="route('admin.projects.index')" :mendesak="$invoiceBelumLunas->isNotEmpty()" :accent="'#2563EB'" />
</div>

<div class="sa-command-grid">
    {{-- Kolom Kiri Utama: Panel Tindakan Prioritas --}}
    <div style="display: flex; flex-direction: column; gap: 16px; min-width: 0;">
        <div class="card sa-card" style="border-top: 4px solid var(--warning, #D97706); margin: 0;">
            <div class="card-title" style="display: flex; align-items: center; gap: 6px;">
                <span>⚡ Prioritas Utama: Butuh Keputusan Anda</span>
                @if ($jumlahTindakan)
                    <span class="badge badge-urgent" style="margin-left: auto;">{{ $jumlahTindakan }} perlu tindakan</span>
                @endif
            </div>
            @forelse ($butuhKeputusan as $item)
                @include('super-admin.dashboard._butuh-keputusan', ['item' => $item])
            @empty
                <x-kosong ikon="circle-check" teks="Tidak ada yang perlu diputuskan." />
            @endforelse
            @if ($jumlahTindakan)
                <a href="{{ route('admin.projects.index', ['tahap' => 'menunggu_acc']) }}" class="sa-lihat-semua">Lihat semua ({{ $jumlahTindakan }}) &rarr;</a>
            @endif
        </div>

        <div class="card sa-card" style="border-top: 4px solid var(--accent, #15803D); margin: 0;">
            <div class="card-title" style="display: flex; align-items: center; gap: 6px;">
                <span>🔔 Staf perlu diingatkan</span>
                @if ($stafTertunda)
                    <span class="badge badge-netral" style="margin-left: auto;">{{ $stafTertunda }} tugas</span>
                @endif
            </div>
            @forelse ($diingatkan as $o)
                @include('super-admin.dashboard._diingatkan', ['o' => $o])
            @empty
                <x-kosong teks="Semua Admin dan Korlap sesuai jadwal." />
            @endforelse
            @if ($stafTertunda)
                @php $tujuanStaf = ($diingatkan->first()['peran'] ?? 'admin') === 'korlap' ? 'super-admin.monitoring.korlap' : 'super-admin.monitoring.admin'; @endphp
                <a href="{{ route($tujuanStaf) }}" class="sa-lihat-semua">Lihat semua &rarr;</a>
            @endif
        </div>
    </div>

    {{-- Kolom Kanan Pendamping: Panel Kalender & Jadwal Sticky --}}
    <div class="sa-sticky-panel">
        <div class="card sa-card" style="margin: 0;">
            <div class="card-title"><i class="ti ti-calendar"></i> Jadwal &amp; Agenda Shooting</div>
            <x-navigasi-timeline :dari="$dariJadwal" :sampai="$sampaiJadwal" :skala="$skala" :action="route('super-admin.dashboard')" />
            <x-agenda :events="$agendaEvents" />
            <a href="{{ route('admin.projects.index') }}" class="sa-lihat-semua">Lihat semua &rarr;</a>
        </div>
    </div>
</div>
@endsection
