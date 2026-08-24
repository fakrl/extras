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
    <div style="font-size: 14px; font-weight: 500; margin-bottom: 10px;">Kelas / Kriteria</div>
    <table>
        <thead><tr><th>Kelas</th><th>Budget</th><th>Kuota</th></tr></thead>
        <tbody>
            @foreach ($castingProject->classes as $class)
                <tr>
                    <td>{{ $class->nama_kelas }}</td>
                    <td>Rp {{ number_format($class->budget_client, 0, ',', '.') }}</td>
                    <td>{{ $class->kuota_kelas }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<form method="POST" action="{{ route('extras.projects.apply', $castingProject) }}">
    @csrf
    <button type="submit" class="btn btn-brand">Daftar ke Proyek Ini</button>
</form>
@endsection
