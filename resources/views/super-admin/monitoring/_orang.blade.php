@php
    $peran = $label === 'Korlap' ? 'korlap' : 'admin';
    $keAmbilAlih = $peran === 'korlap' ? route('admin.attendance.index') : route('admin.projects.index');
@endphp
<details class="mon-orang">
    <summary class="mon-orang-summary">
        <div>
            <strong>{{ $u->name }}</strong>
            <div class="dash-sub">
                {{ $u->proyek_dipegang }} proyek &middot;
                @if ($u->jumlah_tugas)
                    <span @class(['mon-tua' => $u->tugas_tua])>{{ $u->jumlah_tugas }} tugas tertunda</span>
                @else
                    <span class="dash-sub">Tidak ada tugas</span>
                @endif
            </div>
        </div>
        <div class="dash-sub">
            @if ($u->aktivitasTerakhir)
                {{ \App\Models\ActivityLog::actionLabel($u->aktivitasTerakhir->action) }} &middot; {{ $u->aktivitasTerakhir->created_at?->diffForHumans() }}
            @else
                Belum ada aktivitas
            @endif
        </div>
    </summary>
    <div class="mon-orang-isi">
        <button type="submit" form="mon-masuk" name="ke" value="{{ $keAmbilAlih }}" class="btn btn-sm btn-brand" style="margin-bottom: 10px;">Ambil alih sebagai {{ $label }}</button>

        @forelse ($u->kelompok_proyek as $proyekId => $grup)
            <div class="mon-proyek-blok">
                <strong>{{ $grup->first()['proyek'] }}</strong>
                @foreach ($grup as $g)
                    @php $idDialog = 'mon-ingatkan-'.$peran.'-'.$u->id.'-'.$proyekId.'-'.$g['jenis']; @endphp
                    <div class="dash-row">
                        <div class="dash-sub">{{ $g['tugas'] }} &bull; {{ $g['jumlah'] }} Extras &bull; sejak {{ $g['sejak']?->diffForHumans() }}</div>
                        @if ($g['diingatkan_pada'])
                            <span class="dash-sub">Sudah diingatkan {{ $g['diingatkan_pada']->diffForHumans() }}</span>
                        @else
                            <button type="button" class="btn btn-sm" onclick="document.getElementById('{{ $idDialog }}').showModal()">Ingatkan</button>
                        @endif
                    </div>
                    <dialog id="{{ $idDialog }}" class="dash-dialog">
                        <form method="POST" action="{{ $peran === 'korlap' ? route('super-admin.monitoring.korlap.ingatkan') : route('super-admin.pantau-admin.ingatkan') }}" class="dash-dialog-isi">
                            @csrf
                            @if ($peran === 'korlap')
                                <input type="hidden" name="korlap_id" value="{{ $u->id }}">
                                <input type="hidden" name="proyek_id" value="{{ $proyekId }}">
                                <input type="hidden" name="jenis" value="{{ $g['jenis'] }}">
                            @else
                                @foreach ($g['app_ids'] as $id)
                                    <input type="hidden" name="project_application_ids[]" value="{{ $id }}">
                                @endforeach
                            @endif
                            <div class="dash-dialog-judul">Ingatkan {{ $u->name }}?</div>
                            <div class="dash-sub dash-dialog-field">{{ $g['tugas'] }} &bull; {{ $g['jumlah'] }} Extras &bull; {{ $g['proyek'] }}</div>
                            <label for="{{ $idDialog }}-catatan">Catatan <span class="dash-dialog-hint">(boleh dikosongkan)</span></label>
                            <textarea name="catatan" id="{{ $idDialog }}-catatan" rows="2" maxlength="500" class="dash-dialog-field"></textarea>
                            <div class="dash-dialog-btn">
                                <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                                <button type="submit" class="btn btn-sm btn-brand">Kirim Pengingat</button>
                            </div>
                        </form>
                    </dialog>
                @endforeach
            </div>
        @empty
            <p class="dash-sub">Tidak ada tugas tertunda di proyek yang dipegang.</p>
        @endforelse
    </div>
</details>
