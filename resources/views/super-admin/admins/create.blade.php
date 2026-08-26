@extends('layouts.app')

@section('title', 'Tambah Admin')

@section('content')
<div class="card" style="max-width: 560px;">
    <div style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">Tambah Akun Admin</div>

    @if ($errors->any())
        <div class="alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('super-admin.admins.store') }}">
        @csrf
        <label>Nama</label>
        <input type="text" name="name" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <x-password-input name="password" label="Password" :minlength="8" />

        <label>Sub-Role</label>
        <select name="role" required style="width: 100%; margin-bottom: 4px;">
            <option value="admin_default">Admin Default (operasional penuh)</option>
            <option value="admin_talco">Talent Coordinator (Talco)</option>
            <option value="admin_korlap">Koordinator Lapangan (Korlap)</option>
            <option value="admin_sosmed">Sosial Media / Multimedia</option>
        </select>
        <p style="font-size: 12px; color: var(--text-muted); margin: 0 0 14px;">Talco/Korlap/Sosmed adalah cabang kewenangan terbatas, ditugaskan per proyek sesuai kebutuhan.</p>

        <label>Nominal Honor per Event (Rp)</label>
        <input type="number" name="honor_nominal" min="0">
        <p style="font-size: 12px; color: var(--text-muted); margin: -10px 0 18px;">Kosongkan jika tidak relevan (misal untuk Admin Default). Bisa diadjust kapan saja.</p>

        <button type="submit" class="btn btn-brand" style="width: 100%;">Simpan</button>
    </form>
</div>
@endsection
