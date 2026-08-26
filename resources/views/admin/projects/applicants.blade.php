@extends('layouts.app')

@section('title', 'Lineup — ' . $castingProject->nama_produksi)

@php
    $badgeClass = [
        'diajukan' => 'badge-pending', 'direview_admin' => 'badge-pending', 'nego_fee' => 'badge-pending',
        'deal' => 'badge-aktif', 'diajukan_ke_cd' => 'badge-pending', 'direview_cd' => 'badge-pending',
        'lolos' => 'badge-aktif', 'ditolak' => 'badge-tolak', 'kontrak_ditandatangani' => 'badge-aktif',
        'selesai_produksi' => 'badge-aktif', 'dibatalkan' => 'badge-tolak',
    ];
@endphp

@section('content')
<div style="font-size: 16px; font-weight: 600; margin-bottom: 2px;">Lineup — {{ $castingProject->nama_produksi }}</div>
<p style="color: var(--text-secondary); margin: 0 0 20px; font-size: 13.5px;">
    Client: {{ $castingProject->client_ph }} · {{ $applicants->count() }} pendaftar
</p>

@forelse ($applicants as $app)
    <div class="applicant-card">
        <div class="applicant-card-photo">
            @if ($app->extras->foto_profil_path)
                <img src="{{ route('extras.media.foto', $app->extras) }}" alt="Foto {{ $app->extras->alias }}">
            @else
                <div class="thumb-photo-empty"><i class="ti ti-user"></i></div>
            @endif

            @if ($app->extras->photos->isNotEmpty())
                <div class="applicant-card-extra-photos">
                    @foreach ($app->extras->photos as $foto)
                        <a href="{{ route('extras.media.foto-tambahan', [$app->extras, $foto->urutan]) }}" target="_blank">
                            <img src="{{ route('extras.media.foto-tambahan', [$app->extras, $foto->urutan]) }}" alt="Foto {{ $foto->urutan }}" class="thumb-photo-mini">
                        </a>
                    @endforeach
                </div>
            @endif

            @if ($app->extras->video_profil_path)
                <a href="{{ route('extras.media.video', $app->extras) }}" target="_blank" class="btn btn-sm" style="width: 100%; margin-top: 8px; text-align: center;">
                    <i class="ti ti-player-play"></i> Video
                </a>
            @endif
        </div>

        <div>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; flex-wrap: wrap;">
                <div>
                    <div style="font-size: 15px; font-weight: 600;">{{ $app->extras->alias ?? '(belum isi alias)' }}</div>
                    <div style="display: flex; gap: 6px; margin-top: 4px; flex-wrap: wrap;">
                        <span class="badge {{ $badgeClass[$app->status_partisipasi] ?? 'badge-pending' }}">{{ $app->status_partisipasi }}</span>
                        @if ($app->bentrok_jadwal_flag)
                            <span class="badge badge-tolak">Bentrok Jadwal</span>
                        @endif
                        @if ($app->grade)
                            <span class="badge badge-aktif">Grade {{ $app->grade }}</span>
                        @endif
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 12px; color: var(--text-secondary);">Rate Card</div>
                    <div style="font-size: 14px; font-weight: 600;">Rp {{ number_format($app->extras->rate_card ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>

            @if (! empty($app->extras->tautan_tambahan))
                <div style="margin-top: 8px; font-size: 12px; color: var(--text-muted);">
                    @foreach ($app->extras->tautan_tambahan as $i => $tautan)
                        @if ($i > 0) · @endif
                        <a href="{{ $tautan['url'] }}" target="_blank" style="color: var(--accent-strong);">{{ $tautan['label'] }}</a>
                    @endforeach
                </div>
            @endif

            @if ($app->status_partisipasi === 'ditolak' && $app->alasan_tolak)
                <div style="margin-top: 10px; font-size: 12.5px; color: var(--danger);">
                    Alasan ditolak: {{ $app->alasan_tolak }}
                </div>
            @endif

            <div class="entity-card-actions" style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-color);">
                <form method="POST" action="{{ route('admin.applications.grade', $app) }}" style="display: flex; gap: 6px;">
                    @csrf @method('PATCH')
                    <select name="grade" style="width: 70px; min-height: 36px; padding: 4px 8px; margin-bottom: 0;">
                        <option value="A" @selected($app->grade === 'A')>A</option>
                        <option value="B" @selected($app->grade === 'B')>B</option>
                        <option value="C" @selected($app->grade === 'C')>C</option>
                    </select>
                    <button class="btn btn-sm">Set Grade</button>
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
            </div>
        </div>
    </div>

    @if (in_array($app->status_partisipasi, ['diajukan', 'direview_admin'], true))
        <dialog id="reject-dialog-{{ $app->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 360px; width: 90%;">
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
@empty
    <div class="card" style="text-align:center; color: var(--text-muted); padding: 30px 0;">
        Belum ada pendaftar.
    </div>
@endforelse
@endsection
