@extends('layouts.app')

@section('title', 'Monitoring · Korlap')

@include('super-admin.monitoring._gaya')

@section('content')
@include('super-admin.monitoring._kepala', ['mode' => 'korlap', 'teks' => 'Pratinjau lapangan, lihat saja. Klik baris untuk masuk mode Korlap langsung di halaman absensinya. Di mode Korlap, aksi yang Anda lakukan nyata dan tercatat atas nama Anda.'])

<div class="card" style="margin-bottom: 16px;">
    <div class="card-title">Daftar Korlap</div>
    @forelse ($korlaps as $k)
        @include('super-admin.monitoring._orang', ['u' => $k, 'label' => 'Korlap'])
    @empty
        <x-kosong teks="Belum ada Korlap aktif." />
    @endforelse
</div>

@include('partials.korlap-ringkasan', ['masuk' => true])
@endsection
