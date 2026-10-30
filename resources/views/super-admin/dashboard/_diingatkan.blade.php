@php
    $tasks = isset($tasks) ? collect($tasks) : collect([$t]);
    $staf = $tasks->first()['peran'] === 'korlap' ? $tasks->first()['korlap'] : $tasks->first()['admin'];
    $peran = $tasks->first()['peran'];
    $peranLabel = \App\Models\User::LABELS[$peran] ?? ucfirst($peran);
    $idx = $idx ?? ($loop->index ?? 0);
    $jumlahTugas = $tasks->count();
    $totalExtras = $tasks->sum('jumlah');
    $belumDiingatkan = $tasks->whereNull('diingatkan_pada');
    $semuaSudahDiingatkan = $belumDiingatkan->isEmpty();
    $sejakTertua = $tasks->min('sejak');
@endphp

<x-baris-tugas :judul="$staf->name" :meta="$peranLabel.' &middot; '.$jumlahTugas.' tugas tertunda &middot; '.$totalExtras.' Extras'" :umur="'sejak '.($sejakTertua?->diffForHumans() ?? '-')">
    @if ($semuaSudahDiingatkan)
        <button type="button" class="btn btn-sm" onclick="document.getElementById('dash-ingatkan-staf-{{ $idx }}').showModal()" style="color: var(--accent);">
            <i class="ti ti-check"></i> Sudah diingatkan
        </button>
    @else
        <button type="button" class="btn btn-sm btn-brand" onclick="document.getElementById('dash-ingatkan-staf-{{ $idx }}').showModal()">
            <i class="ti ti-bell"></i> Ingatkan
        </button>
    @endif
</x-baris-tugas>

<dialog id="dash-ingatkan-staf-{{ $idx }}" class="dash-dialog" style="max-width: 620px; width: 95%;">
    <div class="dash-dialog-isi" style="max-height: 85vh; display: flex; flex-direction: column;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; gap: 12px;">
            <div>
                <div class="dash-dialog-judul" style="margin-bottom: 2px;">Tugas Tertunda: {{ $staf->name }}</div>
                <div class="dash-sub">{{ $peranLabel }} &bull; {{ $jumlahTugas }} tugas perlu tindakan (total {{ $totalExtras }} Extras)</div>
            </div>
            <button type="button" class="btn-ikon" onclick="this.closest('dialog').close()" aria-label="Tutup dialog" style="border: none; background: transparent; cursor: pointer; font-size: 18px; color: var(--text-muted); padding: 4px;">
                <i class="ti ti-x"></i>
            </button>
        </div>

        @if ($belumDiingatkan->isNotEmpty())
            {{-- Tombol Ingatkan Semua Sekaligus --}}
            <div style="background: var(--md-surface-container, var(--bg-card-hover)); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 12px; margin-bottom: 14px;">
                <form method="POST" action="{{ route('super-admin.pantau-staf.ingatkan-semua') }}">
                    @csrf
                    <input type="hidden" name="staf_id" value="{{ $staf->id }}">
                    <input type="hidden" name="peran" value="{{ $peran }}">
                    <div style="font-size: var(--fs-xs); font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px; letter-spacing: .03em;">
                        Ingatkan Sekaligus
                    </div>
                    <input type="text" name="catatan" placeholder="Catatan pengingat untuk semua tugas..." maxlength="500" class="dash-dialog-field" style="margin-bottom: 8px;">
                    <button type="submit" class="btn btn-sm btn-brand" style="width: 100%;">
                        <i class="ti ti-bell-ringing"></i> Ingatkan Semua Tugas ({{ $belumDiingatkan->count() }})
                    </button>
                </form>
            </div>
        @endif

        <div style="font-size: var(--fs-xs); font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px; letter-spacing: .03em;">
            Rincian Tugas Per Proyek
        </div>

        <div style="overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: 10px; padding-right: 4px;">
            @foreach ($tasks as $tIdx => $t)
                <div style="border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 10px 12px; background: var(--bg-card);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; flex-wrap: wrap;">
                        <div style="flex: 1 1 240px;">
                            <div style="font-weight: 600; font-size: var(--fs-sm); color: var(--text-primary);">{{ $t['proyek'] }}</div>
                            <div class="dash-sub" style="margin-top: 3px; line-height: 1.4;">
                                <strong style="color: var(--text-primary);">{{ $t['tugas'] }}</strong> &bull; {{ $t['jumlah'] }} Extras &bull; sejak {{ $t['sejak']?->diffForHumans() }}
                            </div>
                        </div>
                        <div>
                            @if ($t['diingatkan_pada'])
                                <span class="badge" style="background: var(--bg-card-hover); color: var(--text-muted); padding: 5px 12px; border-radius: 999px; font-size: 11.5px; display: inline-flex; align-items: center; gap: 4px; border: 1px solid var(--border-color);">
                                    <i class="ti ti-check"></i> Sudah diingatkan {{ $t['diingatkan_pada']->diffForHumans() }}
                                </span>
                            @else
                                <form method="POST" action="{{ $t['peran'] === 'korlap' ? route('super-admin.monitoring.korlap.ingatkan') : route('super-admin.pantau-admin.ingatkan') }}" style="margin: 0;">
                                    @csrf
                                    @if ($t['peran'] === 'korlap')
                                        <input type="hidden" name="korlap_id" value="{{ $staf->id }}">
                                        <input type="hidden" name="proyek_id" value="{{ $t['proyek_id'] }}">
                                        <input type="hidden" name="jenis" value="{{ $t['jenis'] }}">
                                    @else
                                        @foreach ($t['app_ids'] as $id)
                                            <input type="hidden" name="project_application_ids[]" value="{{ $id }}">
                                        @endforeach
                                    @endif
                                    <button type="submit" class="btn btn-sm btn-tonal" title="Ingatkan tugas ini">
                                        <i class="ti ti-bell"></i> Ingatkan Tugas Ini
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="dash-dialog-btn" style="margin-top: 14px; border-top: 1px solid var(--border-color); padding-top: 10px;">
            <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Tutup</button>
        </div>
    </div>
</dialog>
