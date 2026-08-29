@extends('layouts.app')

@section('title', 'Kelola Akun CD & Extras')

@section('content')
<div class="card" style="margin-bottom: 20px;">
    <div style="font-size: 14px; font-weight: 500; margin-bottom: 12px;">Casting Director</div>
    <table>
        <thead>
            <tr><th>Nama</th><th>Email</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            @foreach ($castingDirectors as $cd)
                <tr>
                    <td>{{ $cd->name }}</td>
                    <td>{{ $cd->email }}</td>
                    <td>
                        <span class="badge {{ $cd->status === 'aktif' ? 'badge-aktif' : 'badge-tolak' }}">
                            {{ $cd->status }}
                        </span>
                    </td>
                    <td>
                        <form method="POST" action="{{ route('admin.users.toggle-status', $cd) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm">Ubah Status</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="card">
    <div style="font-size: 14px; font-weight: 500; margin-bottom: 12px;">Extras</div>
    <table>
        <thead>
            <tr><th>Nama</th><th>Alias</th><th>Email</th><th>Status</th><th>Pembatalan Mendadak</th><th></th></tr>
        </thead>
        <tbody>
            @foreach ($extras as $ex)
                <tr>
                    <td>{{ $ex->name }}</td>
                    <td>{{ $ex->extrasProfile->alias ?? '-' }}</td>
                    <td>{{ $ex->email }}</td>
                    <td>
                        <span class="badge {{ $ex->status === 'aktif' ? 'badge-aktif' : 'badge-tolak' }}">
                            {{ $ex->status }}
                        </span>
                    </td>
                    <td>
                        {{-- RF-08: cancel_count cuma dari pembatalan mendadak (<H-2), lihat ProjectApplication::batalkan() --}}
                        @php $cancelCount = $ex->extrasProfile->cancel_count ?? 0; @endphp
                        <span class="badge {{ $cancelCount >= 3 ? 'badge-tolak' : ($cancelCount > 0 ? 'badge-pending' : 'badge-aktif') }}">
                            {{ $cancelCount }}x
                        </span>
                    </td>
                    <td>
                        <form method="POST" action="{{ route('admin.users.toggle-status', $ex) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm">Ubah Status</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
