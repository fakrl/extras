@extends('layouts.app')

@php $label = \App\Models\User::LABELS[$mode]; @endphp

@section('title', 'Monitoring · '.$label)

@section('content')
<p style="font-size: 13.5px; color: var(--text-secondary); margin: -8px 0 14px;">
    Pilih akun {{ $label }}. Halaman tampil dengan data akun itu, <strong>lihat saja</strong>: semua aksi (simpan, kirim, TTD) ditolak.
</p>
<form method="GET" action="{{ route('super-admin.mode.pilih', $mode) }}" class="xtoolbar" id="live-form" data-live>
    <input type="search" name="q" value="{{ $q }}" class="xtoolbar-cari" placeholder="Cari nama, username, email…" aria-label="Cari akun">
    <button type="submit" class="btn btn-sm btn-brand" aria-label="Cari"><i class="ti ti-search"></i></button>
    <x-per-halaman :pilihan="\App\Support\PerHalaman::TABEL" :nilai="$akun->perPage()" />
</form>
<div class="card" data-live-target>
    @forelse ($akun as $u)
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--border-color); flex-wrap: wrap;">
            <div style="min-width: 0;">
                <div style="font-weight: 600;">{{ $u->name }}</div>
                <div style="font-size: 12.5px; color: var(--text-muted);">
                    {{ $u->username ? '@'.$u->username : '' }} {{ $u->nama_perusahaan ? '· '.$u->nama_perusahaan : '' }} {{ $u->email ? '· '.$u->email : '' }}
                </div>
            </div>
            <form method="POST" action="{{ route('super-admin.mode.mulai') }}" style="margin: 0;">
                @csrf
                <input type="hidden" name="mode" value="{{ $mode }}">
                <input type="hidden" name="user_id" value="{{ $u->id }}">
                <button type="submit" class="btn btn-sm"><i class="ti ti-eye"></i> Lihat sebagai akun ini</button>
            </form>
        </div>
    @empty
        <div style="padding: 16px 0; color: var(--text-muted); text-align: center;">Tidak ada akun {{ $label }} aktif yang cocok.</div>
    @endforelse
    <x-pagination-bar :paginator="$akun" :pilihan="\App\Support\PerHalaman::TABEL" />
</div>
@endsection
