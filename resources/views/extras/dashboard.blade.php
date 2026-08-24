@extends('layouts.app')

@section('title', 'Dashboard Extras')

@php
    $badgeClass = [
        'diajukan' => 'badge-pending',
        'direview_admin' => 'badge-pending',
        'nego_fee' => 'badge-pending',
        'deal' => 'badge-aktif',
        'diajukan_ke_cd' => 'badge-pending',
        'direview_cd' => 'badge-pending',
        'lolos' => 'badge-aktif',
        'ditolak' => 'badge-tolak',
        'kontrak_ditandatangani' => 'badge-aktif',
        'selesai_produksi' => 'badge-aktif',
        'dibatalkan' => 'badge-tolak',
    ];
    $statusLabel = [
        'diajukan' => 'Diajukan',
        'direview_admin' => 'Direview Admin',
        'nego_fee' => 'Nego Fee',
        'deal' => 'Deal',
        'diajukan_ke_cd' => 'Diajukan ke CD',
        'direview_cd' => 'Direview CD',
        'lolos' => 'Lolos',
        'ditolak' => 'Ditolak',
        'kontrak_ditandatangani' => 'Kontrak TTD',
        'selesai_produksi' => 'Selesai Produksi',
        'dibatalkan' => 'Dibatalkan',
    ];
@endphp

@section('content')
<p style="color: var(--text-secondary); margin: -8px 0 20px; font-size: 13.5px;">
    Halo, {{ auth()->user()->name }}! Cek lowongan casting terbaru dan pantau status pendaftaran kamu di sini.
</p>

<div style="display: flex; gap: 8px; margin-bottom: 20px;">
    <a href="{{ route('extras.profile.show') }}" class="btn">Lihat Profil Saya</a>
    <a href="{{ route('extras.projects.index') }}" class="btn btn-brand">Lihat Lowongan Casting</a>
</div>

<div style="font-size: 14px; font-weight: 500; margin-bottom: 12px;">Pendaftaran Saya</div>

@forelse ($pendaftaranSaya as $app)
    <div class="card" style="margin-bottom: 14px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 14px; flex-wrap: wrap;">
            <div>
                <div style="font-size: 14.5px; font-weight: 600;">{{ $app->castingProject->nama_produksi }}</div>
                <span class="badge {{ $badgeClass[$app->status_partisipasi] ?? 'badge-pending' }}" style="margin-top: 4px; display: inline-block;">
                    {{ $statusLabel[$app->status_partisipasi] ?? $app->status_partisipasi }}
                </span>
            </div>
            @if ($app->status_partisipasi === 'nego_fee')
                <a href="{{ route('extras.negotiations.show', $app) }}" class="btn btn-brand" style="min-height:32px; padding:0 12px; font-size:12.5px;">Lanjut Nego Fee</a>
            @elseif ($app->status_partisipasi === 'lolos')
                <a href="{{ route('contracts.show', $app) }}" class="btn btn-brand" style="min-height:32px; padding:0 12px; font-size:12.5px;">Kontrak</a>
            @elseif (in_array($app->status_partisipasi, ['kontrak_ditandatangani', 'selesai_produksi']))
                <a href="{{ route('payments.show', $app) }}" class="btn btn-brand" style="min-height:32px; padding:0 12px; font-size:12.5px;">Pembayaran</a>
            @endif
        </div>

        @include('partials.application-progress', ['app' => $app])
    </div>
@empty
    <div class="card" style="text-align:center; color: var(--text-muted); padding: 20px 0;">
        Belum ada pendaftaran.
    </div>
@endforelse
@endsection
