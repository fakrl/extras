@extends('layouts.app')

@section('title', 'Lineup: ' . $castingProject->nama_produksi)

@section('content')
<div style="font-size: 16px; font-weight: 600; margin-bottom: 2px;">Lineup: {{ $castingProject->nama_produksi }}</div>
<p style="color: var(--text-secondary); margin: 0 0 20px; font-size: 13.5px;">
    Client: {{ $castingProject->client_ph }} · {{ $applicants->total() }} pendaftar
    @if ($castingProject->wa_group_link)
        · <a href="{{ $castingProject->wa_group_link }}" target="_blank">Grup WA</a>
    @endif
</p>

<div style="display: flex; gap: 6px; margin-bottom: 16px; flex-wrap: wrap;">
    @php $tabs = ['' => 'Semua', 'A' => 'Grade A', 'B' => 'Grade B', 'C' => 'Grade C', 'belum' => 'Belum Dinilai']; @endphp
    @foreach ($tabs as $value => $label)
        <a href="{{ route('admin.projects.applicants', [$castingProject, 'grade' => $value ?: null]) }}"
           class="btn btn-sm {{ ($tab ?? '') !== 'cd' && ($grade ?? '') === $value ? 'btn-brand' : '' }}">{{ $label }}</a>
    @endforeach
    <a href="{{ route('admin.projects.applicants', [$castingProject, 'tab' => 'cd']) }}"
       class="btn btn-sm {{ ($tab ?? '') === 'cd' ? 'btn-brand' : '' }}">Sudah ke Client</a>
</div>

@if (($tab ?? '') !== 'cd')
    <div style="display: flex; gap: 6px; margin-bottom: 16px; flex-wrap: wrap;" aria-label="Filter status">
        @foreach (['' => 'Semua Status'] + \App\Models\ProjectApplication::LABELS as $value => $label)
            <a href="{{ route('admin.projects.applicants', [$castingProject, 'grade' => $grade ?: null, 'status' => $value ?: null]) }}"
               class="btn btn-sm {{ ($status ?? '') === $value ? 'btn-brand' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    <form method="POST" action="{{ route('admin.projects.applicants.bulk', $castingProject) }}" id="bulk-form" class="card" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-bottom: 16px; padding: 12px;">
        @csrf
        <label style="margin: 0; display: flex; align-items: center; gap: 6px;"><input type="checkbox" onclick="document.querySelectorAll('.bulk-check').forEach(c => { if (c.offsetParent !== null) c.checked = this.checked; })"> Pilih semua</label>
        <label for="bulk-grade" style="margin: 0;">Grade</label>
        <select name="grade" id="bulk-grade" style="width: 70px; min-height: 36px; margin-bottom: 0;">
            <option value="A">A</option><option value="B">B</option><option value="C">C</option>
        </select>
        <button type="submit" name="aksi" value="grade" class="btn btn-sm" formnovalidate onclick="var n = document.querySelectorAll('.bulk-check:checked').length; return n > 0 && confirm('Set grade ' + document.getElementById('bulk-grade').value + ' untuk ' + n + ' kandidat? Grade terkunci 2 bulan.');">Set Grade Terpilih</button>
        <button type="button" class="btn btn-sm btn-danger-outline" onclick="document.getElementById('bulk-tolak-dialog').showModal()">Tolak Terpilih</button>
        <dialog id="bulk-tolak-dialog" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 18px; max-width: 360px; width: 90%;">
            <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">Tolak semua kandidat terpilih?</div>
            <label for="bulk-alasan">Alasan penolakan (dikirim ke Extras)</label>
            <textarea name="alasan_tolak" id="bulk-alasan" rows="3" required maxlength="1000" style="width: 100%; margin-bottom: 12px;"></textarea>
            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                <button type="submit" name="aksi" value="tolak" class="btn btn-sm btn-danger-outline">Tolak Terpilih</button>
            </div>
        </dialog>
    </form>
@endif

<div style="position: relative; margin-bottom: 16px;">
    <input type="text" id="search-applicants" placeholder="Cari nama pelamar, alias/username, peran, kelas..."
           style="width: 100%; max-width: 400px; padding: 8px 14px 8px 36px; border: 1px solid var(--border-color); border-radius: 8px; font-size: var(--fs-md); background: var(--bg-card); color: var(--text-primary); margin-bottom: 0;">
    <i class="ti ti-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 15px;"></i>
</div>

<div id="no-applicants-match" class="card" style="display: none; text-align: center; color: var(--text-muted); padding: 24px;">
    Tidak ada pelamar yang sesuai dengan pencarian.
</div>

@if (($tab ?? '') === 'cd')
    @php
        $cdStatusLabel = [
            'diajukan_ke_cd' => 'Menunggu Review Client',
            'direview_cd' => 'Sedang Direview',
            'lolos' => 'Lolos',
            'ditolak' => 'Ditolak',
        ];
        $cdStatusBadge = [
            'diajukan_ke_cd' => 'badge-pending',
            'direview_cd' => 'badge-pending',
            'lolos' => 'badge-aktif',
            'ditolak' => 'badge-tolak',
        ];
    @endphp
    @forelse ($applicants as $app)
        <div class="card applicant-card-item" data-search="{{ strtolower(($app->extras->user->username ?? '') . ' ' . ($app->castingProjectClass->nama_kelas ?? '')) }}" style="margin-bottom: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                <div>
                    <div style="font-weight: 600; font-size: 14px;">{{ $app->extras->user->username ?? '(belum isi username)' }}</div>
                    <div style="font-size: 12.5px; color: var(--text-secondary);">Kelas: {{ $app->castingProjectClass->nama_kelas ?? 'Umum' }}</div>
                </div>
                <span class="badge {{ $cdStatusBadge[$app->status_partisipasi] ?? 'badge-pending' }}">
                    {{ $cdStatusLabel[$app->status_partisipasi] ?? $app->status_partisipasi }}
                </span>
            </div>
        </div>
    @empty
        <div class="card" style="text-align: center; color: var(--text-muted); padding: 20px;">Belum ada kandidat yang diajukan ke Client.</div>
    @endforelse
@else

@forelse ($applicants as $app)
    @php
        $searchString = strtolower(
            ($app->extras->user->username ?? '') . ' ' .
            ($app->extras->user->name ?? '') . ' ' .
            ($app->karakter ?: ($app->castingProjectClass->karakter ?? '')) . ' ' .
            ($app->castingProjectClass->nama_kelas ?? '') . ' ' .
            ($app->status_partisipasi ?? '')
        );
    @endphp
    <div class="applicant-card applicant-card-item" id="app-{{ $app->id }}" data-search="{{ $searchString }}" style="scroll-margin-top: 80px;">
        <div class="applicant-card-photo">
            <label style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;"><input type="checkbox" name="ids[]" value="{{ $app->id }}" form="bulk-form" class="bulk-check"> Pilih</label>
            @if ($app->extras->foto_profil_path)
                <img src="{{ route('extras.media.foto', $app->extras) }}" alt="Foto Extras">
            @else
                <div class="thumb-photo-empty"><i class="ti ti-user"></i></div>
            @endif

            @if ($app->extras->photos->isNotEmpty())
                @php
                $fotosApplicant = $app->extras->photos->map(fn($p) => [
                    'url' => route('extras.media.foto-tambahan', [$app->extras, $p->urutan]),
                    'alt' => 'Foto ' . $p->urutan,
                ])->values()->all();
                @endphp
                @include('partials.foto-lightbox', ['fotos' => $fotosApplicant, 'lightboxId' => 'lb-' . $app->id])
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
                    <div style="font-size: 15px; font-weight: 600;">{{ $app->extras->user->username ?? '(belum isi username)' }}</div>
                    <div style="font-size: 12.5px; color: var(--text-secondary); margin-top: 3px;">
                        Kelas: <strong>{{ $app->castingProjectClass->nama_kelas ?? 'Umum' }}</strong>
                        @if ($app->karakter || $app->castingProjectClass?->karakter)
                            · Peran: <span style="color: var(--accent-strong);">{{ $app->karakter ?: $app->castingProjectClass->karakter }}</span>
                        @endif
                        @if ($app->jam_callingan || $app->castingProjectClass?->jam_callingan)
                            · Callingan: <strong>{{ $app->jam_callingan ?: $app->castingProjectClass->jam_callingan }}</strong>
                            @if ($app->castingProjectClass?->jam_callsheet)
                                <span style="color: var(--text-muted); font-size: var(--fs-xs);">(Callsheet: {{ $app->castingProjectClass->jam_callsheet }})</span>
                            @endif
                        @endif
                        @if ($app->keterangan_scene || $app->castingProjectClass?->keterangan_scene)
                            · Scene: <em>{{ $app->keterangan_scene ?: $app->castingProjectClass->keterangan_scene }}</em>
                        @endif
                    </div>
                    <div style="display: flex; gap: 6px; margin-top: 4px; flex-wrap: wrap;">
                        <x-status-badge :model="$app" />
                        @if ($app->bentrok_jadwal_flag)
                            <span class="badge badge-tolak">Bentrok Jadwal</span>
                        @endif
                        @if ($app->grade)
                            <span class="badge badge-aktif">Rek. Grade (Admin): {{ $app->grade }}</span>
                        @endif
                        @if (($app->tipe_continuity ?: $app->castingProjectClass?->tipe_continuity) === 'continuity')
                            <span class="badge badge-pending">Continuity</span>
                        @endif
                        @if ($app->extras->apresiasi)
                            <span class="badge badge-aktif" title="{{ $app->extras->apresiasi_catatan }}"><i class="ti ti-star-filled"></i> Apresiasi</span>
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
                @php
                    $gradeProfile = $app->extras;
                    $gradeTerkunci = $gradeProfile->grade_diberikan_at && now()->lt($gradeProfile->grade_diberikan_at->addMonths(2));
                    $terkunciSampai = $gradeTerkunci ? $gradeProfile->grade_diberikan_at->addMonths(2)->translatedFormat('d F Y') : null;
                @endphp
                @if ($gradeTerkunci)
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <select disabled style="width: 70px; min-height: 36px; padding: 4px 8px; margin-bottom: 0; opacity: 0.5;">
                            <option>{{ $gradeProfile->grade_saat_ini }}</option>
                        </select>
                        <span style="font-size: 12px; color: var(--text-muted);">Terkunci s.d. {{ $terkunciSampai }}</span>
                    </div>
                @else
                    <form method="POST" action="{{ route('admin.applications.grade', $app) }}" style="display: flex; gap: 6px;">
                        @csrf @method('PATCH')
                        <select name="grade" style="width: 70px; min-height: 36px; padding: 4px 8px; margin-bottom: 0;">
                            <option value="A" @selected($app->grade === 'A')>A</option>
                            <option value="B" @selected($app->grade === 'B')>B</option>
                            <option value="C" @selected($app->grade === 'C')>C</option>
                        </select>
                        <button class="btn btn-sm">Set Grade</button>
                    </form>
                @endif
                <button type="button" class="btn btn-sm" onclick="document.getElementById('breakdown-dialog-{{ $app->id }}').showModal()"><i class="ti ti-movie"></i> Breakdown</button>
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
                @if (auth()->user()->isAdmin() || auth()->user()->isKorlap())
                    <button type="button" class="btn btn-sm" onclick="document.getElementById('catatan-dialog-{{ $app->id }}').showModal()">Catatan Lapangan</button>
                @endif
                @if (auth()->user()->isAdmin())
                    @if ($app->extras->apresiasi)
                        <form method="POST" action="{{ route('admin.applications.apresiasi', $app) }}">
                            @csrf
                            <input type="hidden" name="apresiasi" value="0">
                            <button type="submit" class="btn btn-sm btn-danger-outline">Cabut Apresiasi</button>
                        </form>
                    @else
                        <button type="button" class="btn btn-sm" onclick="document.getElementById('apresiasi-dialog-{{ $app->id }}').showModal()"><i class="ti ti-star"></i> Apresiasi</button>
                    @endif
                @endif
            </div>

            @if ($app->fieldNotes->isNotEmpty())
                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-color);">
                    <div style="font-size: 12.5px; font-weight: 500; margin-bottom: 6px;">Riwayat Catatan Lapangan</div>
                    @foreach ($app->fieldNotes as $note)
                        <div style="font-size: 12.5px; margin-bottom: 6px;">
                            <span class="badge {{ $note->jenis === 'sanksi' ? 'badge-tolak' : 'badge-pending' }}">{{ $note->jenis }}</span>
                            {{ $note->isi }}
                            <span style="color: var(--text-muted);">({{ $note->korlap->name ?? '-' }}, {{ $note->created_at->format('d M Y H:i') }}</span>
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
                <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">Tolak {{ $app->extras->user->username ?? 'kandidat' }}?</div>
                <textarea name="alasan_tolak" rows="3" required placeholder="Contoh: Kriteria tidak sesuai dengan tokoh yang dicari (usia/tinggi/dll)." style="width: 100%; margin-bottom: 12px;"></textarea>
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger-outline">Tolak Kandidat</button>
                </div>
            </form>
        </dialog>
    @endif

    @if (auth()->user()->isAdmin() && ! $app->extras->apresiasi)
        <dialog id="apresiasi-dialog-{{ $app->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 360px; width: 90%;">
            <form method="POST" action="{{ route('admin.applications.apresiasi', $app) }}" style="padding: 18px;">
                @csrf
                <input type="hidden" name="apresiasi" value="1">
                <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">Beri Apresiasi ke {{ $app->extras->user->username ?? 'kandidat' }}?</div>
                <textarea name="apresiasi_catatan" rows="3" maxlength="1000" placeholder="Catatan internal (opsional), mis. alasan diapresiasi." style="width: 100%; margin-bottom: 12px;"></textarea>
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                    <button type="submit" class="btn btn-sm btn-brand">Simpan Apresiasi</button>
                </div>
            </form>
        </dialog>
    @endif

    <dialog id="breakdown-dialog-{{ $app->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 440px; width: 90%;">
        <form method="POST" action="{{ route('admin.applications.breakdown', $app) }}" style="padding: 18px;">
            @csrf @method('PATCH')
            <div style="font-size: 15px; font-weight: 600; margin-bottom: 12px;">Breakdown: {{ $app->extras->user->username ?? 'Extras' }}</div>
            <div style="margin-bottom: 10px;">
                <label>Nama Karakter / Peran</label>
                <input type="text" name="karakter" value="{{ old('karakter', $app->karakter ?: $app->castingProjectClass?->karakter) }}" placeholder="misal: Preman 1 / Teman Kampus" style="width: 100%;">
            </div>
            <div class="form-row" style="margin-bottom: 10px;">
                <div>
                    <label>Jam Callingan Extras</label>
                    <input type="time" name="jam_callingan" value="{{ old('jam_callingan', $app->jam_callingan ?: $app->castingProjectClass?->jam_callingan) }}" style="width: 100%;">
                </div>
                <div>
                    <label>Tipe Kontinuitas</label>
                    <select name="tipe_continuity" style="width: 100%;">
                        <option value="free" @selected(($app->tipe_continuity ?: $app->castingProjectClass?->tipe_continuity) === 'free')>Bebas</option>
                        <option value="continuity" @selected(($app->tipe_continuity ?: $app->castingProjectClass?->tipe_continuity) === 'continuity')>Continuity</option>
                    </select>
                </div>
            </div>
            <div style="margin-bottom: 12px;">
                <label>Keterangan Scene</label>
                <input type="text" name="keterangan_scene" value="{{ old('keterangan_scene', $app->keterangan_scene ?: $app->castingProjectClass?->keterangan_scene) }}" placeholder="misal: Scene 12-14 warung kopi, baju casual" style="width: 100%;">
            </div>
            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                <button type="submit" class="btn btn-sm btn-brand">Simpan Breakdown</button>
            </div>
        </form>
    </dialog>

    @if ($app->status_partisipasi === 'deal')
        <dialog id="batalkan-dialog-{{ $app->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 360px; width: 90%;">
            <form method="POST" action="{{ route('admin.negotiations.batalkan', $app) }}" style="padding: 18px;">
                @csrf
                <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">Batalkan {{ $app->extras->user->username ?? 'kandidat' }}?</div>
                <textarea name="alasan" rows="3" required placeholder="Alasan pembatalan" style="width: 100%; margin-bottom: 12px;"></textarea>
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger-outline">Batalkan Aplikasi</button>
                </div>
            </form>
        </dialog>
    @endif

    @if (auth()->user()->isAdmin() || auth()->user()->isKorlap())
        <dialog id="catatan-dialog-{{ $app->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 360px; width: 90%;">
            <form method="POST" action="{{ route('admin.applications.catatan', $app) }}" style="padding: 18px;">
                @csrf
                <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">Catatan Lapangan: {{ $app->extras->user->username ?? 'kandidat' }}</div>
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
@endif

{{ $applicants->links() }}

@push('scripts')
<script>
(function () {
    var searchInput = document.getElementById('search-applicants');
    var cards = document.querySelectorAll('.applicant-card-item');
    var noMatch = document.getElementById('no-applicants-match');
    if (!searchInput) return;

    searchInput.addEventListener('input', function () {
        var q = this.value.toLowerCase().trim();
        var visibleCount = 0;
        cards.forEach(function (card) {
            var text = card.dataset.search || card.textContent.toLowerCase();
            var match = !q || text.includes(q);
            card.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });
        if (noMatch) {
            noMatch.style.display = (visibleCount === 0 && q.length > 0) ? 'block' : 'none';
        }
    });
})();
</script>
@endpush
@endsection
