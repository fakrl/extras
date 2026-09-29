@extends('layouts.app')

@section('title', $castingProject->nama_produksi)

@section('content')
<p style="color: var(--text-secondary); margin: -8px 0 20px; font-size: 13.5px;">
    Client: {{ $castingProject->client_ph }} · Deadline: {{ $castingProject->deadline->format('d M Y') }}
</p>

<div class="card" style="margin-bottom: 16px;">
    <div style="font-size: 14px; font-weight: 500; margin-bottom: 10px;">Tanggal Shooting</div>
    <ul style="margin: 0; padding-left: 18px; font-size: 13.5px;">
        @foreach ($castingProject->shootingDates as $date)
            <li>{{ $date->tanggal->format('d M Y') }}</li>
        @endforeach
    </ul>
</div>

<div class="card" style="margin-bottom: 16px;">
    <div style="font-size: 14px; font-weight: 500; margin-bottom: 10px;">Karakter yang Dibutuhkan</div>
    @if ($castingProject->classes->isEmpty())
        <p style="margin: 0; font-size: 13.5px; color: var(--text-muted);">Belum ada rincian peran.</p>
    @else
        @include('partials.peran-lowongan', ['classes' => $castingProject->classes])
    @endif
</div>

@if (! $castingProject->menerimaPendaftaran())
<div class="alert-info">Proyek ini sudah tidak menerima pendaftaran (ditutup, kuota penuh, atau lewat deadline).</div>
@else
<form method="POST" action="{{ route('extras.projects.apply', $castingProject) }}">
    @csrf
    @if ($castingProject->classes->isNotEmpty())
        <div class="card" style="margin-bottom: 16px;">
            <div style="font-size: 14px; font-weight: 500; margin-bottom: 10px;">Pilih Karakter yang Kamu Daftar</div>
            @foreach ($castingProject->classes as $class)
                @php($penuh = $class->sisaKuota() === 0)
                <label style="display: block; margin-bottom: 8px;{{ $penuh ? ' color: var(--text-muted);' : '' }}">
                    <input type="radio" name="casting_project_class_id" value="{{ $class->id }}" required @disabled($penuh)>
                    {{ $class->nama_kelas }}
                    @if ($penuh)
                        <span style="font-size: var(--fs-xs);">· Penuh, slot bisa terbuka lagi</span>
                    @endif
                </label>
            @endforeach
        </div>
    @endif
    <button type="submit" class="btn btn-brand">Daftar ke Proyek Ini</button>
</form>
@endif
@endsection
