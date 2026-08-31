@extends('layouts.app')

@section('title', 'Absensi Lapangan')

@section('content')
<div style="font-size: 16px; font-weight: 600; margin-bottom: 2px;">Absensi Lapangan</div>
<p style="color: var(--text-secondary); margin: 0 0 16px; font-size: 13.5px;">
    Tandai hadir/tidak hadir Extras per tanggal shooting.
</p>

<form method="GET" action="{{ route('admin.attendance.index') }}" class="form-row" style="margin-bottom: 8px;">
    <div>
        <label>Proyek</label>
        <select name="project" onchange="this.form.submit()">
            @foreach ($projects as $p)
                <option value="{{ $p->id }}" @selected($castingProject?->id === $p->id)>{{ $p->nama_produksi }} — {{ $p->client_ph }}</option>
            @endforeach
        </select>
    </div>
    @if ($castingProject && $castingProject->shootingDates->isNotEmpty())
        <div>
            <label>Tanggal Shooting</label>
            <select name="tanggal" onchange="this.form.submit()">
                @foreach ($castingProject->shootingDates as $tgl)
                    <option value="{{ $tgl->id }}" @selected($shootingDate?->id === $tgl->id)>{{ $tgl->tanggal->format('d M Y') }}</option>
                @endforeach
            </select>
        </div>
    @endif
</form>

@if (! $castingProject)
    <div class="card" style="text-align:center; color: var(--text-muted); padding: 30px 0;">Belum ada proyek.</div>
@elseif (! $shootingDate)
    <div class="card" style="text-align:center; color: var(--text-muted); padding: 30px 0;">Proyek ini belum punya tanggal shooting.</div>
@else
    @forelse ($applicants as $app)
        @php $absen = $app->attendances->firstWhere('event_shooting_date_id', $shootingDate->id); @endphp
        <div class="entity-card" style="margin-bottom: 12px;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap;">
                <div>
                    <div class="entity-card-title">{{ $app->extras->alias_tampil ?? '(belum isi alias)' }}</div>
                    @if ($absen)
                        <span class="badge {{ $absen->status === 'hadir' ? 'badge-aktif' : 'badge-tolak' }}">{{ $absen->status === 'hadir' ? 'Hadir' : 'Tidak Hadir' }}</span>
                    @else
                        <span class="badge badge-pending">Belum diabsen</span>
                    @endif
                </div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <form method="POST" action="{{ route('admin.attendance.store', $app) }}">
                        @csrf
                        <input type="hidden" name="event_shooting_date_id" value="{{ $shootingDate->id }}">
                        <input type="hidden" name="status" value="hadir">
                        <button class="btn btn-brand" style="min-width: 110px;">Hadir</button>
                    </form>
                    <form method="POST" action="{{ route('admin.attendance.store', $app) }}">
                        @csrf
                        <input type="hidden" name="event_shooting_date_id" value="{{ $shootingDate->id }}">
                        <input type="hidden" name="status" value="tidak_hadir">
                        <button class="btn btn-danger-outline" style="min-width: 110px;">Tidak Hadir</button>
                    </form>
                    <button type="button" class="btn" onclick="document.getElementById('catatan-dialog-{{ $app->id }}').showModal()">Catatan</button>
                </div>
            </div>
        </div>

        <dialog id="catatan-dialog-{{ $app->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 360px; width: 90%;">
            <form method="POST" action="{{ route('admin.applications.catatan', $app) }}" style="padding: 18px;">
                @csrf
                <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">Catatan Lapangan — {{ $app->extras->alias_tampil ?? 'kandidat' }}</div>
                <select name="jenis" required style="width: 100%; margin-bottom: 10px;">
                    <option value="catatan">Catatan</option>
                    <option value="sanksi">Sanksi</option>
                </select>
                <textarea name="isi" rows="3" required placeholder="Isi catatan/sanksi" style="width: 100%; margin-bottom: 12px;"></textarea>
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                    <button type="submit" class="btn btn-sm btn-brand">Simpan</button>
                </div>
            </form>
        </dialog>
    @empty
        <div class="card" style="text-align:center; color: var(--text-muted); padding: 30px 0;">Tidak ada Extras aktif di proyek ini.</div>
    @endforelse
@endif
@endsection
