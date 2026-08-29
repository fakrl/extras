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
    @if ($castingProject->wa_group_link)
        · <a href="{{ $castingProject->wa_group_link }}" target="_blank">Grup WA</a>
    @endif
</p>

<div style="display: flex; gap: 6px; margin-bottom: 16px; flex-wrap: wrap;">
    @php $tabs = ['' => 'Semua', 'A' => 'Grade A', 'B' => 'Grade B', 'C' => 'Grade C', 'belum' => 'Belum Dinilai']; @endphp
    @foreach ($tabs as $value => $label)
        <a href="{{ route('admin.projects.applicants', [$castingProject, 'grade' => $value ?: null]) }}"
           class="btn btn-sm {{ ($grade ?? '') === $value ? 'btn-brand' : '' }}">{{ $label }}</a>
    @endforeach
</div>

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
                @if ($app->status_partisipasi === 'deal')
                    <button type="button" class="btn btn-sm btn-danger-outline" onclick="document.getElementById('batalkan-dialog-{{ $app->id }}').showModal()">Batalkan</button>
                @endif
                @if ($app->status_partisipasi === 'lolos' || $app->contract)
                    <a href="{{ route('contracts.show', $app) }}" class="btn btn-sm">Kontrak</a>
                @endif
                @if ($app->status_partisipasi === 'kontrak_ditandatangani' || $app->payment)
                    <a href="{{ route('payments.show', $app) }}" class="btn btn-sm">Bayar</a>
                @endif
                @if (in_array(auth()->user()->role, ['admin_default', 'admin_korlap'], true))
                    <button type="button" class="btn btn-sm" onclick="document.getElementById('catatan-dialog-{{ $app->id }}').showModal()">Catatan Lapangan</button>
                @endif
            </div>

            @if ($app->fieldNotes->isNotEmpty())
                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-color);">
                    <div style="font-size: 12.5px; font-weight: 500; margin-bottom: 6px;">Riwayat Catatan Lapangan</div>
                    @foreach ($app->fieldNotes as $note)
                        <div style="font-size: 12.5px; margin-bottom: 6px;">
                            <span class="badge {{ $note->jenis === 'sanksi' ? 'badge-tolak' : 'badge-pending' }}">{{ $note->jenis }}</span>
                            {{ $note->isi }}
                            <span style="color: var(--text-muted);">— {{ $note->korlap->name ?? '-' }}, {{ $note->created_at->format('d M Y H:i') }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
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

    @if ($app->status_partisipasi === 'deal')
        <dialog id="batalkan-dialog-{{ $app->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 360px; width: 90%;">
            <form method="POST" action="{{ route('admin.negotiations.batalkan', $app) }}" style="padding: 18px;">
                @csrf
                <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">Batalkan {{ $app->extras->alias ?? 'kandidat' }}?</div>
                <textarea name="alasan" rows="3" required placeholder="Alasan pembatalan" style="width: 100%; margin-bottom: 12px;"></textarea>
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger-outline">Batalkan Aplikasi</button>
                </div>
            </form>
        </dialog>
    @endif

    @if (in_array(auth()->user()->role, ['admin_default', 'admin_korlap'], true))
        <dialog id="catatan-dialog-{{ $app->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 360px; width: 90%;">
            <form method="POST" action="{{ route('admin.applications.catatan', $app) }}" style="padding: 18px;">
                @csrf
                <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">Catatan Lapangan — {{ $app->extras->alias ?? 'kandidat' }}</div>
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
    @endif
@empty
    <div class="card" style="text-align:center; color: var(--text-muted); padding: 30px 0;">
        Belum ada pendaftar.
    </div>
@endforelse
@endsection
