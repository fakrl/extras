{{-- Modal Preview Akun (M3 Pop-up Dialog) --}}
<dialog id="modal-akun-preview-{{ $u->id }}" class="dash-dialog" style="max-width: 540px; width: 95%; border-radius: var(--radius-lg); border: 1px solid var(--border-color); background: var(--bg-card); box-shadow: var(--shadow-modal);">
    <div class="dash-dialog-isi" style="display: flex; flex-direction: column; gap: 14px; max-height: 85vh; overflow-y: auto;">
        {{-- Header Modal --}}
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
            <div style="display: flex; gap: 12px; align-items: center; min-width: 0;">
                <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--bg-nav-active); color: var(--accent-strong); font-size: 20px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid var(--border-color); overflow: hidden;">
                    @if ($u->role === 'extras' && $u->extrasProfile?->foto_profil_path)
                        <img src="{{ route('extras.media.foto', $u->extrasProfile) }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        {{ strtoupper(substr($u->name, 0, 1)) }}
                    @endif
                </div>
                <div style="min-width: 0;">
                    <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                        <h3 style="margin: 0; font-size: var(--fs-md); font-weight: 700; color: var(--text-primary); line-height: 1.2;">
                            {{ $u->name }}
                        </h3>
                        <x-status-badge :model="$u" />
                        <span class="badge {{ $statusBadge($u)[1] }}">{{ $statusBadge($u)[0] }}</span>
                    </div>
                    <div style="font-size: var(--fs-xs); color: var(--text-muted); margin-top: 3px;">
                        {{ implode(' · ', array_filter([$u->username ? '@'.$u->username : null, $u->email ?? 'tanpa email'])) }}
                    </div>
                </div>
            </div>
            <button type="button" class="btn-ikon" onclick="this.closest('dialog').close()" aria-label="Tutup" style="border: none; background: transparent; cursor: pointer; font-size: 18px; color: var(--text-muted); padding: 4px; flex-shrink: 0;">
                <i class="ti ti-x"></i>
            </button>
        </div>

        {{-- Detail Ringkas Akun --}}
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; background: var(--md-surface-container, var(--bg-card-hover)); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 12px;">
            <div>
                <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: .03em;">WhatsApp / Telepon</div>
                <div style="font-size: var(--fs-sm); font-weight: 600; color: var(--text-primary); margin-top: 2px;">
                    @if ($u->nomor_wa)
                        <a href="https://wa.me/{{ $u->nomor_wa }}" target="_blank" rel="noopener" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                            <i class="ti ti-brand-whatsapp" style="color: #25d366;"></i> +{{ $u->nomor_wa }}
                        </a>
                    @else
                        –
                    @endif
                </div>
            </div>

            <div>
                <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: .03em;">Terdaftar Pada</div>
                <div style="font-size: var(--fs-sm); color: var(--text-primary); margin-top: 2px;">
                    {{ $u->created_at->translatedFormat('d M Y') }}
                </div>
            </div>

            @if ($u->role === 'extras' && $u->extrasProfile)
                <div>
                    <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: .03em;">Grade Saat Ini</div>
                    <div style="font-size: var(--fs-sm); font-weight: 600; color: var(--text-primary); margin-top: 2px;">
                        {{ $u->extrasProfile->grade_saat_ini ? 'Grade '.$u->extrasProfile->grade_saat_ini : 'Belum dinilai' }}
                    </div>
                </div>

                <div>
                    <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: .03em;">Usia / Domisili</div>
                    <div style="font-size: var(--fs-sm); color: var(--text-primary); margin-top: 2px;">
                        {{ implode(' · ', array_filter([$u->extrasProfile->usia ? $u->extrasProfile->usia.' thn' : null, $u->extrasProfile->domisili])) ?: '–' }}
                    </div>
                </div>
            @elseif ($u->nama_perusahaan)
                <div style="grid-column: 1 / -1;">
                    <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: .03em;">Perusahaan / PH</div>
                    <div style="font-size: var(--fs-sm); font-weight: 600; color: var(--text-primary); margin-top: 2px;">
                        {{ $u->nama_perusahaan }}
                    </div>
                </div>
            @elseif ($u->honor_nominal)
                <div>
                    <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: .03em;">Honor per Event</div>
                    <div style="font-size: var(--fs-sm); font-weight: 600; color: var(--accent); margin-top: 2px;">
                        Rp {{ number_format($u->honor_nominal, 0, ',', '.') }}
                    </div>
                </div>
            @endif
        </div>

        @if ($u->role === 'extras' && $u->extrasProfile && $u->extrasProfile->categories->isNotEmpty())
            <div>
                <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: .03em; margin-bottom: 6px;">Kategori / Look</div>
                <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                    @foreach ($u->extrasProfile->categories as $kat)
                        <span class="badge" style="background: var(--bg-card-hover); color: var(--text-secondary); border: 1px solid var(--border-color); font-size: 11.5px; padding: 3px 8px;">
                            #{{ $kat->nama }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif


        {{-- Footer & Aksi --}}
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 4px; padding-top: 12px; border-top: 1px solid var(--border-color);">
            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                <button type="button" class="btn btn-sm btn-tonal" onclick="this.closest('dialog').close()">
                    Tutup
                </button>
                @unless ($u->is_protected || $u->trashed())
                    <button type="button" class="btn btn-sm btn-tonal" onclick="this.closest('dialog').close(); document.getElementById('edit-user-dialog-{{ $u->id }}').showModal()">
                        <i class="ti {{ $u->role === 'extras' ? 'ti-adjustments' : 'ti-edit' }}"></i> {{ $u->role === 'extras' ? 'Kelola Tag & Grade' : 'Edit' }}
                    </button>
                @endunless
            </div>

            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                @if ($u->role === 'extras' && $u->extrasProfile)
                    <a href="{{ route('admin.extras.profil', $u) }}" class="btn btn-sm btn-tonal" data-profil-modal data-aksi-url="{{ route('super-admin.admins.show', $u) }}" data-aksi-label="Buka Akun Penuh" onclick="this.closest('dialog').close()">
                        <i class="ti ti-user"></i> Portofolio
                    </a>
                @endif
                @unless ($u->trashed() || $u->is_protected)
                    <a href="{{ route('super-admin.admins.show', $u) }}" class="btn btn-sm btn-brand">
                        Buka Akun Penuh &rarr;
                    </a>
                @endunless
            </div>
        </div>
    </div>
</dialog>
