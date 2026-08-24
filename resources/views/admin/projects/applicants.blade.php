@extends('layouts.app')

@section('title', 'Pendaftar — ' . $castingProject->nama_produksi)

@php
    $badgeClass = [
        'diajukan' => 'badge-pending', 'direview_admin' => 'badge-pending', 'nego_fee' => 'badge-pending',
        'deal' => 'badge-aktif', 'diajukan_ke_cd' => 'badge-pending', 'direview_cd' => 'badge-pending',
        'lolos' => 'badge-aktif', 'ditolak' => 'badge-tolak', 'kontrak_ditandatangani' => 'badge-aktif',
        'selesai_produksi' => 'badge-aktif', 'dibatalkan' => 'badge-tolak',
    ];
@endphp

@section('content')
<p style="color: var(--text-secondary); margin: -8px 0 20px; font-size: 13.5px;">
    Client: {{ $castingProject->client_ph }}
</p>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Foto</th><th>Alias</th><th>Rate Card</th><th>Bentrok?</th><th>Status</th><th>Grade</th><th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($applicants as $app)
                <tr>
                    <td>
                        @if ($app->extras->foto_profil_path)
                            <img src="{{ route('extras.media.foto', $app->extras) }}" alt="Foto {{ $app->extras->alias }}" class="thumb-photo">
                        @else
                            <div class="thumb-photo thumb-photo-empty"><i class="ti ti-user"></i></div>
                        @endif
                    </td>
                    <td>
                        {{ $app->extras->alias ?? '(belum isi alias)' }}
                        @if ($app->extras->video_profil_path)
                            <br><a href="{{ route('extras.media.video', $app->extras) }}" target="_blank" style="font-size: 11.5px; color: var(--accent-strong);">Lihat video</a>
                        @endif
                        @if ($app->extras->photos->isNotEmpty())
                            <br>
                            @foreach ($app->extras->photos as $foto)
                                <a href="{{ route('extras.media.foto-tambahan', [$app->extras, $foto->urutan]) }}" target="_blank">
                                    <img src="{{ route('extras.media.foto-tambahan', [$app->extras, $foto->urutan]) }}" alt="Foto {{ $foto->urutan }}" class="thumb-photo-mini">
                                </a>
                            @endforeach
                        @endif
                        @if (! empty($app->extras->tautan_tambahan))
                            <br><span style="font-size: 11px; color: var(--text-muted);">
                                @foreach ($app->extras->tautan_tambahan as $i => $tautan)
                                    @if ($i > 0) · @endif
                                    <a href="{{ $tautan['url'] }}" target="_blank" style="color: var(--accent-strong);">{{ $tautan['label'] }}</a>
                                @endforeach
                            </span>
                        @endif
                    </td>
                    <td>Rp {{ number_format($app->extras->rate_card ?? 0, 0, ',', '.') }}</td>
                    <td>
                        @if ($app->bentrok_jadwal_flag)
                            <span class="badge badge-tolak">Bentrok</span>
                        @else
                            <span style="color: var(--text-muted);">-</span>
                        @endif
                    </td>
                    <td><span class="badge {{ $badgeClass[$app->status_partisipasi] ?? 'badge-pending' }}">{{ $app->status_partisipasi }}</span></td>
                    <td>{{ $app->grade ?? '-' }}</td>
                    <td style="display: flex; gap: 6px; flex-wrap: wrap;">
                        <form method="POST" action="{{ route('admin.applications.grade', $app) }}" style="display: flex; gap: 6px;">
                            @csrf @method('PATCH')
                            <select name="grade" style="width: 70px; min-height: 32px; padding: 4px 8px; margin-bottom: 0;">
                                <option value="A" @selected($app->grade === 'A')>A</option>
                                <option value="B" @selected($app->grade === 'B')>B</option>
                                <option value="C" @selected($app->grade === 'C')>C</option>
                            </select>
                            <button class="btn btn-sm">Set</button>
                        </form>
                        <a href="{{ route('admin.negotiations.show', $app) }}" class="btn btn-sm btn-brand">Nego Fee</a>
                        @if (in_array($app->status_partisipasi, ['diajukan', 'direview_admin'], true))
                            <button type="button" class="btn btn-sm btn-danger-outline" onclick="document.getElementById('reject-dialog-{{ $app->id }}').showModal()">Tolak</button>
                        @endif
                        @if ($app->status_partisipasi === 'lolos' || $app->contract)
                            <a href="{{ route('contracts.show', $app) }}" class="btn btn-sm">Kontrak</a>
                        @endif
                        @if ($app->status_partisipasi === 'kontrak_ditandatangani' || $app->payment)
                            <a href="{{ route('payments.show', $app) }}" class="btn btn-sm">Bayar</a>
                        @endif
                        @if ($app->status_partisipasi === 'ditolak' && $app->alasan_tolak)
                            <span style="font-size: 11px; color: var(--text-muted);" title="{{ $app->alasan_tolak }}">Alasan: {{ \Illuminate\Support\Str::limit($app->alasan_tolak, 40) }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center; color: var(--text-muted); padding: 20px 0;">Belum ada pendaftar.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Dialog konfirmasi reject-dini, di luar <table> supaya HTML valid --}}
@foreach ($applicants as $app)
    @if (in_array($app->status_partisipasi, ['diajukan', 'direview_admin'], true))
        <dialog id="reject-dialog-{{ $app->id }}" style="border: 1px solid var(--border); border-radius: 10px; padding: 0; max-width: 360px; width: 90%;">
            <form method="POST" action="{{ route('admin.applications.reject', $app) }}" style="padding: 18px;">
                @csrf @method('PATCH')
                <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">Tolak {{ $app->extras->alias ?? 'kandidat' }}?</div>
                <textarea name="alasan_tolak" rows="3" required placeholder="Contoh: Kriteria tidak sesuai dengan tokoh yang dicari (usia/tinggi/dll)." style="width: 100%; margin-bottom: 12px;"></textarea>
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger-outline">Tolak Kandidat</button>
                </div>
            </form>
        </dialog>
    @endif
@endforeach
@endsection
