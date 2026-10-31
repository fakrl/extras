{{-- Modal Ringkasan Proyek (Material Design 3) --}}
@props(['project', 'id' => null])
@php
    $modalId = $id ?? 'modal-proyek-'.$project->id;
    $tahapProyek = $project->tahap();
    $kuota = (int) $project->kuota;
    $ket = $project->keteranganShooting();
@endphp
<dialog id="{{ $modalId }}" class="modal-proyek-dialog">
    <div class="mp-head">
        <div>
            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                <span class="kode-proyek">{{ $project->kode_proyek }}</span>
                @if ($project->isUrgent() && $project->status === 'dibuka')
                    <span class="badge badge-urgent">Urgent</span>
                @endif
                <span class="badge {{ $project->status === 'dibuka' ? 'badge-success' : 'badge-netral' }}">
                    {{ ucfirst($tahapProyek) }}
                </span>
            </div>
            <h3 style="margin: 4px 0 0; font-size: var(--fs-lg); font-weight: 600; color: var(--text-primary); line-height: 1.3;">{{ $project->nama_produksi }}</h3>
        </div>
        <button type="button" class="btn-ikon" onclick="document.getElementById('{{ $modalId }}').close()" aria-label="Tutup" style="border: none; background: transparent; cursor: pointer; font-size: 18px; color: var(--text-muted); display: inline-flex; align-items: center; justify-content: center;"><i class="ti ti-x"></i></button>
    </div>
    <div class="mp-body">
        <div class="mp-card-box">
            <span class="mp-label"><i class="ti ti-building" style="margin-right: 4px;"></i> Client / PH</span>
            @if ($project->client)
                <div style="font-weight: 600; color: var(--text-primary); font-size: var(--fs-base);">{{ $project->namaClient() }}</div>
                @if ($project->client->nama_perusahaan && $project->client->name !== $project->client->nama_perusahaan)
                    <div style="color: var(--text-secondary); font-size: var(--fs-xs); margin-top: 2px;">{{ $project->client->name }}</div>
                @endif
                @if ($project->client?->telepon)
                    <div style="color: var(--text-muted); font-size: var(--fs-xs); margin-top: 4px;"><i class="ti ti-phone" style="font-size: 11px;"></i> {{ $project->client->telepon }}</div>
                @endif
            @else
                <span class="tp-kosong">Belum ada Client</span>
            @endif
        </div>

        <div>
            <span class="mp-label"><i class="ti ti-users" style="margin-right: 4px;"></i> Tim Pengelola</span>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                <div class="mp-card-box" style="padding: 8px 12px;">
                    <div style="font-size: var(--fs-xs); color: var(--text-muted); margin-bottom: 2px;">PIC</div>
                    <div style="font-weight: 600; color: var(--text-primary); font-size: var(--fs-sm);">{{ $project->admin?->name ?? 'Belum ada PIC' }}</div>
                </div>
                <div class="mp-card-box" style="padding: 8px 12px;">
                    <div style="font-size: var(--fs-xs); color: var(--text-muted); margin-bottom: 2px;">Korlap</div>
                    <div style="font-weight: 600; color: var(--text-primary); font-size: var(--fs-sm);">{{ $project->korlap()?->name ?? 'Belum ada Korlap' }}</div>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
            <div class="mp-card-box" style="padding: 8px 12px;">
                <span class="mp-label"><i class="ti ti-calendar" style="margin-right: 4px;"></i> Jadwal Shooting</span>
                @if ($ket)
                    <div style="font-weight: 600; color: var(--text-primary); font-size: var(--fs-sm);">{{ $project->rentangShooting() }}</div>
                    <div style="color: var(--text-muted); font-size: var(--fs-xs); margin-top: 2px;">{{ $ket }}</div>
                @else
                    <span class="tp-kosong" style="font-size: var(--fs-xs);">Belum dijadwalkan</span>
                @endif
            </div>
            <div class="mp-card-box" style="padding: 8px 12px;">
                <span class="mp-label"><i class="ti ti-user-check" style="margin-right: 4px;"></i> Pendaftar / Kuota</span>
                <div style="font-weight: 600; color: var(--text-primary); font-size: var(--fs-sm);">{{ $project->applications_count }} / {{ $kuota }} extras</div>
                @if ($kuota > 0)
                    <div class="tp-bar" style="margin-top: 6px;"><i style="width: {{ min(100, round($project->applications_count / $kuota * 100)) }}%;"></i></div>
                @endif
            </div>
        </div>

        @if ($project->deskripsi)
            <div>
                <span class="mp-label"><i class="ti ti-notes" style="margin-right: 4px;"></i> Deskripsi Proyek</span>
                <div class="mp-card-box" style="font-size: var(--fs-xs); color: var(--text-secondary); line-height: 1.5; max-height: 90px; overflow-y: auto; white-space: pre-wrap;">{{ $project->deskripsi }}</div>
            </div>
        @endif
    </div>
    <div class="mp-foot">
        <button type="button" class="btn btn-sm btn-tonal" onclick="document.getElementById('{{ $modalId }}').close()">Tutup</button>
        <a href="{{ route('admin.projects.show', $project) }}" class="btn btn-sm btn-brand" style="display: inline-flex; align-items: center; gap: 6px;">Buka Halaman Proyek <i class="ti ti-arrow-right"></i></a>
    </div>
</dialog>
