@extends('layouts.app')

@section('title', 'Pantau Admin')

@push('styles')
<style>
.sa-head-judul { font-size: 18px; font-weight: 700; margin: 0 0 16px; }
.pa-filter { margin-bottom: 16px; max-width: 280px; }
</style>
@endpush

@section('content')
<h1 class="sa-head-judul">Pantau Admin</h1>

<form method="GET" action="{{ route('super-admin.pantau-admin.index') }}" class="pa-filter">
    <label for="pa-admin">Admin</label>
    <select name="admin_id" id="pa-admin" onchange="this.form.submit()">
        <option value="">Semua Admin</option>
        @foreach ($adminsAktif as $a)
            <option value="{{ $a->id }}" @selected((int) $adminId === $a->id)>{{ $a->name }}</option>
        @endforeach
    </select>
</form>

<div class="card">
    <p class="dash-sub">Tugas tertunda tiap Admin aktif di proyeknya, terurut paling lama tertunda. Klik Ingatkan untuk kirim pengingat in-app ke Admin PIC.</p>
    @forelse ($pantauAdmin as $p)
        @include('super-admin.dashboard._pantau-admin', ['p' => $p])
    @empty
        <x-kosong teks="Tidak ada tugas tertunda di Admin mana pun saat ini." />
    @endforelse
</div>
@endsection
