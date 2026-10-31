@php
    $url = $e->url_proyek ?? null;
    $modalId = 'modal-agenda-'.($e->id ?? ($itemIndex ?? uniqid()));
    $jamText = '';
    if (! empty($e->jam_mulai)) {
        $jamText = substr($e->jam_mulai, 0, 5) . (! empty($e->jam_selesai) ? ' – ' . substr($e->jam_selesai, 0, 5) : '');
    }
@endphp

<div class="dash-row agenda-card-row" style="cursor: pointer; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 14px; border-radius: var(--radius-md); border: 1px solid var(--border-color); background: var(--bg-card); margin-bottom: 8px; transition: background .15s ease, border-color .15s ease;" onclick="document.getElementById('{{ $modalId }}').showModal()">
    <div style="display: flex; align-items: center; gap: 10px; min-width: 0; flex: 1;">
        @if ($jamText)
            <span class="badge badge-netral" style="font-size: 11.5px; font-weight: 600; white-space: nowrap; padding: 4px 8px; display: inline-flex; align-items: center; gap: 4px;">
                <i class="ti ti-clock"></i> {{ $jamText }}
            </span>
        @endif
        <div style="min-width: 0; flex: 1;">
            <strong style="color: var(--text-primary); font-size: var(--fs-sm); display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                {{ $e->nama_produksi }}
            </strong>
        </div>
    </div>

    <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
        @if (! empty($e->absensi))
            <span class="badge" style="background: var(--bg-card-hover); color: var(--text-secondary); border: 1px solid var(--border-color); font-size: 11.5px; padding: 4px 10px; display: inline-flex; align-items: center; gap: 4px;">
                <i class="ti ti-user-check"></i> {{ $e->absensi }}
            </span>
        @elseif (! empty($e->jumlah_extras))
            <span class="badge" style="background: var(--bg-card-hover); color: var(--text-secondary); border: 1px solid var(--border-color); font-size: 11.5px; padding: 4px 10px;">
                {{ $e->jumlah_extras }} Extras
            </span>
        @endif
        <button type="button" class="btn btn-sm btn-tonal" onclick="event.stopPropagation(); document.getElementById('{{ $modalId }}').showModal()" style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; font-size: 12px;">
            <i class="ti ti-info-circle"></i> Info
        </button>
    </div>
</div>

{{-- Pop-up Modal Detail Jadwal --}}
<dialog id="{{ $modalId }}" class="dash-dialog" style="max-width: 520px; width: 95%; border-radius: var(--radius-lg); border: 1px solid var(--border-color); background: var(--bg-card); box-shadow: var(--shadow-modal);">
    <div class="dash-dialog-isi" style="display: flex; flex-direction: column; gap: 14px;">
        {{-- Header Modal --}}
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
            <div>
                <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px; flex-wrap: wrap;">
                    <span class="badge badge-brand" style="font-size: 11.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                        <i class="ti ti-calendar-event"></i> {{ isset($tanggalCarbon) ? $tanggalCarbon->translatedFormat('l, d F Y') : \Illuminate\Support\Carbon::parse($e->tanggal)->translatedFormat('l, d F Y') }}
                    </span>
                    @if ($jamText)
                        <span class="badge badge-netral" style="font-size: 11.5px; font-weight: 600;">
                            {{ $jamText }}
                        </span>
                    @endif
                </div>
                <h3 style="margin: 0; font-size: var(--fs-md); font-weight: 700; color: var(--text-primary); line-height: 1.3;">
                    {{ $e->nama_produksi }}
                </h3>
            </div>
            <button type="button" class="btn-ikon" onclick="this.closest('dialog').close()" aria-label="Tutup" style="border: none; background: transparent; cursor: pointer; font-size: 18px; color: var(--text-muted); padding: 4px;">
                <i class="ti ti-x"></i>
            </button>
        </div>

        {{-- Detail Informasi --}}
        <div style="display: flex; flex-direction: column; gap: 12px;">
            @if (! empty($e->lokasi))
                <div style="display: flex; gap: 10px; align-items: flex-start;">
                    <i class="ti ti-map-pin" style="font-size: 18px; color: var(--accent); margin-top: 2px; flex-shrink: 0;"></i>
                    <div>
                        <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: .03em;">Lokasi Shooting</div>
                        <div style="font-size: var(--fs-sm); font-weight: 600; color: var(--text-primary); margin-top: 2px;">{{ $e->lokasi }}</div>
                    </div>
                </div>
            @endif

            @if (! empty($e->absensi) || ! empty($e->jumlah_extras))
                <div style="display: flex; gap: 10px; align-items: flex-start;">
                    <i class="ti ti-users" style="font-size: 18px; color: var(--accent); margin-top: 2px; flex-shrink: 0;"></i>
                    <div>
                        <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: .03em;">Partisipasi & Absensi</div>
                        <div style="font-size: var(--fs-sm); color: var(--text-primary); margin-top: 2px;">
                            <strong>{{ $e->absensi ?: $e->jumlah_extras.' Extras terdaftar' }}</strong>
                            @if (! empty($e->jumlah_extras) && ! empty($e->absensi))
                                <span class="dash-sub">(dari total {{ $e->jumlah_extras }} Extras)</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if (! empty($e->catatan))
                <div style="background: var(--md-surface-container, var(--bg-card-hover)); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 10px 12px;">
                    <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: .03em; margin-bottom: 4px; display: flex; align-items: center; gap: 5px;">
                        <i class="ti ti-notes"></i> Catatan & Instruksi
                    </div>
                    <div style="font-size: var(--fs-sm); color: var(--text-primary); line-height: 1.45;">
                        {{ $e->catatan }}
                    </div>
                </div>
            @endif
        </div>

        {{-- Footer Modal --}}
        <div class="dash-dialog-btn" style="margin-top: 6px; padding-top: 12px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="btn btn-sm btn-tonal" onclick="this.closest('dialog').close()">
                Tutup
            </button>
            <div style="display: flex; gap: 8px;">
                @if ($url)
                    <a href="{{ $url }}" class="btn btn-sm btn-tonal" style="display: inline-flex; align-items: center; gap: 5px;">
                        Buka Proyek &rarr;
                    </a>
                @endif
                @if (! empty($e->url_absensi))
                    <a href="{{ $e->url_absensi }}" class="btn btn-sm btn-brand" style="display: inline-flex; align-items: center; gap: 5px;">
                        <i class="ti ti-clipboard-check"></i> Absensi &rarr;
                    </a>
                @endif
            </div>
        </div>
    </div>
</dialog>
