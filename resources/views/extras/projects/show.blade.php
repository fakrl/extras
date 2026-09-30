@extends('layouts.app')

@section('title', $castingProject->nama_produksi)

@section('content')
<p style="color: var(--text-secondary); margin: -8px 0 20px; font-size: 13.5px;">
    Client: {{ $castingProject->namaClient() }} · Deadline: {{ $castingProject->deadline->format('d M Y') }}
</p>

<div class="card" style="margin-bottom: 16px;">
    <div style="font-size: 14px; font-weight: 500; margin-bottom: 10px;">Tanggal Shooting</div>
    <ul style="margin: 0; padding-left: 18px; font-size: 13.5px;">
        @foreach ($castingProject->shootingDates as $date)
            @php($kena = $bentrok->filter(fn ($b) => $b->tanggalBentrok->contains($date->tanggal->toDateString())))
            <li style="margin-bottom: 4px;">
                {{ $date->tanggal->format('d M Y') }}
                @if ($kena->isNotEmpty())
                    <span class="badge badge-tolak">Bentrok dengan jadwalmu</span>
                    <span style="font-size: var(--fs-xs); color: var(--text-muted);">{{ $kena->map(fn ($b) => $b->castingProject->nama_produksi.' ('.($b->isPasti() ? 'sudah pasti' : 'masih diproses').')')->join(', ') }}</span>
                @endif
            </li>
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

@if (session('konfirmasi_bentrok'))
    <dialog id="dialog-bentrok" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 380px; width: 90%;">
        <form method="POST" action="{{ route('extras.projects.apply', $castingProject) }}" style="padding: 18px;">
            @csrf
            <input type="hidden" name="konfirmasi_bentrok" value="1">
            @if (old('casting_project_class_id'))
                <input type="hidden" name="casting_project_class_id" value="{{ old('casting_project_class_id') }}">
            @endif
            <div style="font-size: 14px; font-weight: 600; margin-bottom: 8px;"><span class="badge badge-tolak">Bentrok jadwal</span></div>
            <p style="font-size: 13px; margin: 0 0 14px; line-height: 1.5;">{{ session('konfirmasi_bentrok') }}</p>
            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                <button type="submit" class="btn btn-sm btn-brand">Tetap daftar</button>
            </div>
        </form>
    </dialog>
    <script>document.getElementById('dialog-bentrok').showModal();</script>
@endif
@endif
@endsection
