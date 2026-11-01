{{-- Modal Ringkasan Proyek (Material Design 3) --}}
@props(['project', 'id' => null])
@php
    $modalId = $id ?? 'modal-proyek-'.$project->id;
    $tahapProyek = $project->tahap();
    $kuota = (int) $project->kuota;
    $ket = $project->keteranganShooting();
    $bisaKelola = auth()->user()?->isSuperAdmin() || auth()->user()?->isAdmin();
    $pics = $pics ?? \App\Models\User::where('role', \App\Models\User::ROLE_ADMIN)->where('status', 'aktif')->orderBy('name')->get();
    $korlaps = $korlaps ?? \App\Models\User::where('role', \App\Models\User::ROLE_KORLAP)->where('status', 'aktif')->orderBy('name')->get();
    $clients = $clients ?? \App\Models\User::where('role', \App\Models\User::ROLE_CLIENT)->where('status', 'aktif')->orderBy('name')->get();
    $currentKorlap = $project->korlap();
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
        @if ($bisaKelola)
            <form method="POST" action="{{ route('admin.projects.pengelola', $project) }}" class="mp-form-pengelola" onsubmit="event.preventDefault(); window.simpanPengelolaProyek(this);">
                @csrf
                @method('PATCH')
                <div class="mp-card-box" style="margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <span class="mp-label" style="margin-bottom: 0;"><i class="ti ti-building" style="margin-right: 4px;"></i> Client / PH</span>
                        <span class="mp-save-badge" style="display: none; font-size: 11px; font-weight: 600; color: #16a34a;"><i class="ti ti-check"></i> Tersimpan</span>
                    </div>
                    <select name="client_id" class="mp-select" onchange="window.simpanPengelolaProyek(this.form)">
                        <option value="">-- Belum ada Client --</option>
                        @foreach ($clients as $c)
                            <option value="{{ $c->id }}" @selected($project->client_id === $c->id)>
                                {{ $c->nama_perusahaan ? $c->nama_perusahaan . ' (' . $c->name . ')' : $c->name }}
                            </option>
                        @endforeach
                    </select>
                    @if ($project->client?->telepon)
                        <div style="color: var(--text-muted); font-size: var(--fs-xs); margin-top: 6px;"><i class="ti ti-phone" style="font-size: 11px;"></i> {{ $project->client->telepon }}</div>
                    @endif
                </div>

                <div style="margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <span class="mp-label" style="margin-bottom: 0;"><i class="ti ti-users" style="margin-right: 4px;"></i> Tim Pengelola</span>
                        <span class="mp-save-badge" style="display: none; font-size: 11px; font-weight: 600; color: #16a34a;"><i class="ti ti-check"></i> Tersimpan</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <div class="mp-card-box" style="padding: 8px 12px;">
                            <div style="font-size: var(--fs-xs); color: var(--text-muted); margin-bottom: 4px;">PIC (Admin)</div>
                            <select name="admin_id" class="mp-select" onchange="window.simpanPengelolaProyek(this.form)">
                                <option value="">-- Belum ada PIC --</option>
                                @foreach ($pics as $p)
                                    <option value="{{ $p->id }}" @selected($project->admin_id === $p->id)>
                                        {{ $p->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mp-card-box" style="padding: 8px 12px;">
                            <div style="font-size: var(--fs-xs); color: var(--text-muted); margin-bottom: 4px;">Korlap</div>
                            <select name="korlap_id" class="mp-select" onchange="window.simpanPengelolaProyek(this.form)">
                                <option value="">-- Belum ada Korlap --</option>
                                @foreach ($korlaps as $k)
                                    <option value="{{ $k->id }}" @selected($currentKorlap?->id === $k->id)>
                                        {{ $k->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        @else
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
        @endif

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

<style>
.mp-select {
    width: 100%;
    padding: 6px 8px;
    font-size: var(--fs-xs);
    font-weight: 600;
    color: var(--text-primary);
    background: var(--bg-card-hover, var(--bg-card));
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    outline: none;
    cursor: pointer;
    transition: border-color .15s ease, background .15s ease;
}
.mp-select:hover {
    border-color: var(--accent);
}
.mp-select:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 2px rgba(13, 148, 136, .2);
}
</style>

<script>
if (!window.simpanPengelolaProyek) {
    window.simpanPengelolaProyek = function(form) {
        var badges = form.querySelectorAll('.mp-save-badge');
        var formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function(res) {
            if (res.ok) {
                badges.forEach(function(b) {
                    b.style.display = 'inline-flex';
                    setTimeout(function() { b.style.display = 'none'; }, 2500);
                });
            } else {
                form.submit();
            }
        })
        .catch(function() {
            form.submit();
        });
    };
}
</script>
