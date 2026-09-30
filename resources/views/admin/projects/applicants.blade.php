@extends('layouts.app')

@section('title', 'Lineup: ' . $castingProject->nama_produksi)

@section('content')
<div style="font-size: 16px; font-weight: 600; margin-bottom: 2px;">Lineup: {{ $castingProject->nama_produksi }}</div>
<p style="color: var(--text-secondary); margin: 0 0 20px; font-size: 13.5px;">
    Client: {{ $castingProject->namaClient() }} · {{ $applicants->total() }} pendaftar
    @if ($castingProject->link_grup)
        · <a href="{{ $castingProject->link_grup }}" target="_blank">Grup koordinasi</a>
    @endif
</p>

<div class="xfilter">
    @php $tabs = ['' => 'Semua', 'A' => 'Grade A', 'B' => 'Grade B', 'C' => 'Grade C', 'belum' => 'Belum Dinilai']; @endphp
    @foreach ($tabs as $value => $label)
        <a href="{{ route('admin.projects.applicants', [$castingProject, 'grade' => $value ?: null]) }}"
           class="btn btn-sm {{ ($tab ?? '') !== 'client' && ($grade ?? '') === $value ? 'btn-brand' : '' }}">{{ $label }}</a>
    @endforeach
    <a href="{{ route('admin.projects.applicants', [$castingProject, 'tab' => 'client']) }}"
       class="btn btn-sm {{ ($tab ?? '') === 'client' ? 'btn-brand' : '' }}">Sudah ke Client</a>
</div>

@if (($tab ?? '') !== 'client')
    <div class="xfilter" aria-label="Filter status">
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
            <label for="bulk-alasan">Alasan penolakan (dikirim ke Extras) <span class="wajib" aria-hidden="true">*</span></label>
            <textarea name="alasan_tolak" id="bulk-alasan" rows="3" required maxlength="1000" style="width: 100%; margin-bottom: 12px;"></textarea>
            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                <button type="submit" name="aksi" value="tolak" class="btn btn-sm btn-danger-outline">Tolak Terpilih</button>
            </div>
        </dialog>
    </form>
@endif

<div class="xfilter" aria-label="Filter tag dan urutan">
    @if ($tagDicari->isNotEmpty())
        <span class="xfilter-label">Tag dicari</span>
        @foreach ($tagDicari as $tag)
            @php $on = in_array($tag->id, $tagIds); @endphp
            <a href="{{ request()->fullUrlWithQuery(['tag' => ($on ? array_values(array_diff($tagIds, [$tag->id])) : [...$tagIds, $tag->id]) ?: null, 'page' => null]) }}"
               class="btn btn-sm {{ $on ? 'btn-brand' : '' }}" @if ($on) aria-current="true" @endif>#{{ $tag->nama }}</a>
        @endforeach
    @endif
    <span class="xfilter-label" style="margin-left: auto;">Urutkan</span>
    <a href="{{ request()->fullUrlWithQuery(['urut' => null, 'page' => null]) }}" class="btn btn-sm {{ $urut ? '' : 'btn-brand' }}">Terbaru</a>
    <a href="{{ request()->fullUrlWithQuery(['urut' => 'cocok', 'page' => null]) }}" class="btn btn-sm {{ $urut === 'cocok' ? 'btn-brand' : '' }}">Paling cocok</a>
    <a href="{{ request()->fullUrlWithQuery(['urut' => 'favorit', 'page' => null]) }}" class="btn btn-sm {{ $urut === 'favorit' ? 'btn-brand' : '' }}">Favorit dulu</a>
</div>
@if ($tagDicari->isNotEmpty())
    <p class="xfilter-note">Menampilkan yang punya <strong>salah satu</strong> tag{{ $urut === 'cocok' ? ', diurutkan paling cocok' : '' }}</p>
@endif

{{-- live search server-side (lintas halaman paginasi); filter aktif ikut sebagai hidden input --}}
<form method="GET" action="{{ route('admin.projects.applicants', $castingProject) }}" id="live-form" data-live role="search" style="display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px; margin-bottom: 16px;">
    @foreach (request()->except(['q', 'page', 'per', 'favorit']) as $k => $v)
        @foreach ((array) $v as $vv)
            <input type="hidden" name="{{ is_array($v) ? $k.'[]' : $k }}" value="{{ $vv }}">
        @endforeach
    @endforeach
    <span style="position: relative; flex: 1 1 260px; max-width: 400px;">
    <label for="search-applicants" class="sr-only">Cari pelamar</label>
    <input type="search" name="q" id="search-applicants" value="{{ $cari }}" placeholder="Cari nama pelamar, alias/username, peran, kelas..."
           style="width: 100%; padding: 8px 14px 8px 36px; border: 1px solid var(--border-color); border-radius: 8px; font-size: var(--fs-md); background: var(--bg-card); color: var(--text-primary); margin-bottom: 0;">
    <i class="ti ti-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 15px;"></i>
    </span>
    <x-per-halaman :pilihan="\App\Support\PerHalaman::KARTU" :nilai="$applicants->perPage()" />
    <x-filter-panel :filter="[$favorit ? ['favorit', '⭐ Favorit'] : null]">
        <label class="fswitch">⭐ Favorit <input type="checkbox" name="favorit" value="1" @checked($favorit)></label>
    </x-filter-panel>
</form>

<div data-live-target>
@if ($applicants->isEmpty())
    <div class="card" style="text-align: center; color: var(--text-muted); padding: 24px;">
        {{ $cari !== '' ? 'Tidak ada pelamar yang sesuai dengan pencarian.' : 'Belum ada pelamar di filter ini.' }}
    </div>
@endif

@php
    $klienStatusLabel = [
        'diajukan_ke_client' => ['Menunggu Review Client', 'badge-pending'],
        'lolos' => ['Lolos', 'badge-aktif'],
        'ditolak' => ['Ditolak', 'badge-tolak'],
    ];
    $isAdmin = auth()->user()->bisaSebagaiAdmin();
    $bisaCatatan = $isAdmin || auth()->user()->bisaSebagaiKorlap();
@endphp

<div class="xgrid">
@forelse ($applicants as $app)
    @php
        $ex = $app->extras;
        $alias = $ex->user->username ?? 'kandidat';
        $aksi = match (true) {
            in_array($app->status_partisipasi, ['diajukan', 'direview_admin'], true) => ['label' => 'Mulai Nego', 'href' => route('admin.negotiations.show', $app)],
            $app->status_partisipasi === 'nego_fee' => ['label' => 'Lanjut Nego', 'href' => route('admin.negotiations.show', $app)],
            $app->status_partisipasi === 'deal' => ['label' => 'Ajukan ke Client', 'post' => route('admin.negotiations.ajukan-ke-client', $app), 'confirm' => "Ajukan @{$alias} ke Client?"],
            $app->status_partisipasi === 'lolos' => ['label' => 'Siapkan Kontrak', 'href' => route('contracts.show', $app)],
            $app->status_partisipasi === 'kontrak_ditandatangani' || (bool) $app->payment => ['label' => 'Pembayaran', 'href' => route('payments.show', $app)],
            default => null,
        };
    @endphp
    @include('partials.extras-card', [
        'profile' => $ex,
        'aplikasi' => $app,
        'badge' => ($tab ?? '') === 'client' ? ($klienStatusLabel[$app->status_partisipasi] ?? null) : null,
        'check' => ($tab ?? '') !== 'client' ? ['name' => 'ids[]', 'class' => 'bulk-check', 'form' => 'bulk-form'] : null,
        'sub' => implode(' · ', array_filter([$ex->user->name ?? null, $app->grade ? 'Grade '.$app->grade : null])),
        'lihat' => $ex->user ? ['href' => route('admin.extras.profil', $ex->user), 'data-profil-modal' => true, 'data-aksi-dialog' => 'detail-'.$app->id, 'data-aksi-label' => 'Detail & aksi'] : ['onclick' => "document.getElementById('detail-{$app->id}').showModal()"],
        'aksi' => $aksi,
        'favorit' => true,
        'peringatan' => $app->bentrok_jadwal_flag ? 'Bentrok jadwal' : null,
        'attrs' => [
            'id' => 'app-'.$app->id,
            'class' => 'applicant-card-item',
            'data-search' => strtolower(($ex->user->username ?? '').' '.($ex->user->name ?? '').' '.$app->karakter.' '.($app->castingProjectClass->nama_kelas ?? '').' '.$app->status_partisipasi),
        ],
    ])
@empty
    <div class="card" style="grid-column: 1 / -1; text-align: center; color: var(--text-muted); padding: 30px 0;">
        {{ ($tab ?? '') === 'client' ? 'Belum ada kandidat yang diajukan ke Client.' : 'Belum ada pendaftar.' }}
    </div>
@endforelse
</div>

@foreach ($applicants as $app)
    @php
        $ex = $app->extras;
        $alias = $ex->user->username ?? 'kandidat';
        $gradeTerkunci = $ex->grade_diberikan_at && now()->lt($ex->grade_diberikan_at->addMonths(2));
        $kelas = $app->castingProjectClass;
    @endphp
    <dialog class="xmodal" id="detail-{{ $app->id }}" aria-label="Detail {{ $alias }}" onclick="if (event.target === this) this.close()">
        <div class="xmodal-ph" style="--h: {{ crc32((string) ($ex->user->username ?? '')) % 360 }};">
            @if ($ex->foto_profil_path)
                <img src="{{ route('extras.media.foto', $ex) }}" alt="Foto {{ $alias }}" loading="lazy">
            @else
                <span class="xcard-inisial" aria-hidden="true">{{ $ex->user->username ? strtoupper(mb_substr($ex->user->username, 0, 2)) : '?' }}</span>
            @endif
            <button type="button" class="xmodal-x" aria-label="Tutup" onclick="this.closest('dialog').close()"><i class="ti ti-x"></i></button>
        </div>
        <div class="xmodal-body">
            <div class="xmodal-head">
                <div style="min-width: 0;">
                    <div class="xmodal-name">{{ $ex->user->username ? '@'.$ex->user->username : '(belum isi username)' }}</div>
                    <div class="xmodal-sub">{{ $ex->user->name ?? '' }}</div>
                </div>
                <x-status-badge :model="$app" />
            </div>
            <div class="xmodal-badges">
                @if ($app->bentrok_jadwal_flag)
                    <span class="badge badge-tolak">Bentrok Jadwal</span>
                @endif
                @if ($app->grade)
                    <span class="badge badge-aktif">Rek. Grade (Admin): {{ $app->grade }}</span>
                @endif
                @if ($app->tipe_continuity === 'continuity')
                    <span class="badge badge-pending">Continuity</span>
                @endif
                @if ($ex->apresiasi)
                    <span class="badge badge-aktif" title="{{ $ex->apresiasi_catatan }}">⭐ Favorit</span>
                @endif
            </div>

            <div style="margin-top: 10px;">@include('partials.toggle-beranda', ['profile' => $ex])</div>

            @if ($app->status_partisipasi === 'ditolak' && $app->alasan_tolak)
                <div class="alert-danger" style="margin: 12px 0 0;">Alasan ditolak: {{ $app->alasan_tolak }}</div>
            @endif

            @include('partials.tag-cocok', ['profile' => $ex, 'aplikasi' => $app])
            @if ($isAdmin && $ex->user)
                <details style="margin-top: 10px;">
                    <summary class="btn btn-sm" style="list-style: none;"><i class="ti ti-tags"></i> Ubah tag</summary>
                    <form method="POST" action="{{ route('admin.users.kategori', $ex->user) }}" style="margin-top: 10px;">
                        @csrf @method('PATCH')
                        @include('partials.tag-input', ['name' => 'tag_nama', 'selected' => $ex->categories])
                        <button type="submit" class="btn btn-sm btn-brand" style="margin-top: 10px;">Simpan Tag</button>
                    </form>
                </details>
            @endif

            <div class="xsec">Peran & breakdown</div>
            <div class="xkv">
                <div><span class="xkv-l">Kelas</span><b>{{ $kelas->nama_kelas ?? 'Umum' }}</b></div>
                <div><span class="xkv-l">Rate card</span><b>Rp {{ number_format($ex->rate_card ?? 0, 0, ',', '.') }}</b></div>
                <div><span class="xkv-l">Peran</span><b>{{ $app->karakter ?: '-' }}</b></div>
                <div><span class="xkv-l">Callingan</span><b>{{ $app->jam_callingan ?: '-' }}</b>@if ($kelas?->jam_callsheet)<span class="xkv-l">Callsheet {{ $kelas->jam_callsheet }}</span>@endif</div>
                @if ($app->keterangan_scene)
                    <div class="full"><span class="xkv-l">Scene</span><b>{{ $app->keterangan_scene }}</b></div>
                @endif
            </div>

            <div class="xsec">Fisik</div>
            <div class="xkv">
                <div><span class="xkv-l">Usia</span><b>{{ $ex->usia ? $ex->usia.' th' : '-' }}</b></div>
                <div><span class="xkv-l">Tinggi</span><b>{{ $ex->tinggi_badan ? $ex->tinggi_badan.' cm' : '-' }}</b></div>
                <div><span class="xkv-l">Gender</span><b>{{ $ex->gender ? ucfirst($ex->gender) : '-' }}</b></div>
                <div><span class="xkv-l">Ukuran baju</span><b>{{ $ex->ukuran_baju ?: '-' }}</b></div>
            </div>

            @if ($ex->riwayat_pengalaman)
                <div class="xsec">Pengalaman</div>
                @foreach ($ex->pengalamanUrut() as $pg)
                    <div class="xrow"><span>{{ $pg['judul'] }}@if (! empty($pg['keterangan']))<span class="xrow-muted"> · {{ $pg['keterangan'] }}</span>@endif</span><span class="xrow-muted">{{ $pg['tahun'] ?? '' }}</span></div>
                @endforeach
            @endif

            @if ($ex->fotoTambahan()->isNotEmpty() || $ex->video_profil_path || ! empty($ex->tautan_tambahan))
                <div class="xsec">Galeri & tautan</div>
                @if ($ex->fotoTambahan()->isNotEmpty())
                    @include('partials.foto-lightbox', ['fotos' => $ex->fotoTambahan()->keys()->map(fn ($slot) => ['url' => route('extras.media.foto-tambahan', [$ex, $slot]), 'alt' => 'Foto '.$slot])->values()->all(), 'lightboxId' => 'lb-'.$app->id])
                @endif
                @if ($ex->video_profil_path)
                    <a href="{{ route('extras.media.video', $ex) }}" target="_blank" class="btn btn-sm" style="margin-top: 8px;"><i class="ti ti-player-play"></i> Video</a>
                @endif
                @if (! empty($ex->tautan_tambahan))
                    <div style="margin-top: 8px; font-size: var(--fs-sm);">
                        @foreach ($ex->tautan_tambahan as $i => $tautan)
                            @if ($i > 0) · @endif
                            <a href="{{ $tautan['url'] }}" target="_blank" style="color: var(--accent-strong);">{{ $tautan['label'] }}</a>
                        @endforeach
                    </div>
                @endif
            @endif

            @if ($app->fieldNotes->isNotEmpty())
                <div class="xsec">Catatan lapangan</div>
                @foreach ($app->fieldNotes as $note)
                    <div class="xrow" style="justify-content: flex-start;">
                        <span class="badge {{ $note->jenis === 'sanksi' ? 'badge-tolak' : 'badge-pending' }}">{{ $note->jenis }}</span>
                        <span>{{ $note->isi }} <span class="xrow-muted">({{ $note->korlap->name ?? '-' }}, {{ $note->created_at->format('d M Y H:i') }})</span></span>
                    </div>
                @endforeach
            @endif

            <div class="xsec">Grade</div>
            @if ($gradeTerkunci)
                <div style="display: flex; align-items: center; gap: 8px;">
                    <select disabled style="width: 70px; margin-bottom: 0; opacity: 0.5;"><option>{{ $ex->grade_saat_ini }}</option></select>
                    <span style="font-size: var(--fs-xs); color: var(--text-muted);">Terkunci s.d. {{ $ex->grade_diberikan_at->addMonths(2)->translatedFormat('d F Y') }}</span>
                </div>
            @else
                <form method="POST" action="{{ route('admin.applications.grade', $app) }}" style="display: flex; gap: 8px;">
                    @csrf @method('PATCH')
                    <select name="grade" aria-label="Grade" style="width: 80px; margin-bottom: 0;">
                        <option value="A" @selected($app->grade === 'A')>A</option>
                        <option value="B" @selected($app->grade === 'B')>B</option>
                        <option value="C" @selected($app->grade === 'C')>C</option>
                    </select>
                    <button class="btn">Set Grade</button>
                </form>
            @endif

            <div class="xsec">Aksi lain</div>
            <div class="xaksi">
                <a href="{{ route('admin.negotiations.show', $app) }}" class="btn btn-brand">Nego Fee</a>
                <button type="button" class="btn" onclick="document.getElementById('breakdown-dialog-{{ $app->id }}').showModal()"><i class="ti ti-movie"></i> Breakdown</button>
                @if ($app->status_partisipasi === 'lolos' || $app->contract)
                    <a href="{{ route('contracts.show', $app) }}" class="btn">Kontrak</a>
                @endif
                @if ($app->status_partisipasi === 'kontrak_ditandatangani' || $app->payment)
                    <a href="{{ route('payments.show', $app) }}" class="btn">Bayar</a>
                @endif
                @if ($bisaCatatan)
                    <button type="button" class="btn" onclick="document.getElementById('catatan-dialog-{{ $app->id }}').showModal()">Catatan Lapangan</button>
                @endif
                @if ($isAdmin)
                    @if ($ex->apresiasi)
                        <form method="POST" action="{{ route('admin.applications.apresiasi', $app) }}">
                            @csrf
                            <input type="hidden" name="apresiasi" value="0">
                            <button type="submit" class="btn btn-danger-outline">Hapus dari Favorit</button>
                        </form>
                    @else
                        <button type="button" class="btn" onclick="document.getElementById('apresiasi-dialog-{{ $app->id }}').showModal()"><i class="ti ti-star"></i> Jadikan Favorit</button>
                    @endif
                @endif
                @if ($ex->user)
                    <a href="{{ route('admin.extras.profil', $ex->user) }}" class="btn" data-profil-modal><i class="ti ti-user"></i> Profil lengkap</a>
                @endif
                @if (in_array($app->status_partisipasi, ['diajukan', 'direview_admin'], true))
                    <button type="button" class="btn btn-danger-outline" onclick="document.getElementById('reject-dialog-{{ $app->id }}').showModal()">Tolak</button>
                @endif
                @if ($app->status_partisipasi === 'deal')
                    <button type="button" class="btn btn-danger-outline" onclick="document.getElementById('batalkan-dialog-{{ $app->id }}').showModal()">Batalkan</button>
                @endif
            </div>
        </div>
    </dialog>

    @if (in_array($app->status_partisipasi, ['diajukan', 'direview_admin'], true))
        <dialog id="reject-dialog-{{ $app->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 360px; width: 90%;">
            <form method="POST" action="{{ route('admin.applications.reject', $app) }}" style="padding: 18px;">
                @csrf @method('PATCH')
                <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">Tolak {{ $alias }}?</div>
                <textarea name="alasan_tolak" rows="3" required placeholder="Contoh: Kriteria tidak sesuai dengan tokoh yang dicari (usia/tinggi/dll)." style="width: 100%; margin-bottom: 12px;"></textarea>
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger-outline">Tolak Kandidat</button>
                </div>
            </form>
        </dialog>
    @endif

    @if ($isAdmin && ! $ex->apresiasi)
        <dialog id="apresiasi-dialog-{{ $app->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 360px; width: 90%;">
            <form method="POST" action="{{ route('admin.applications.apresiasi', $app) }}" style="padding: 18px;">
                @csrf
                <input type="hidden" name="apresiasi" value="1">
                <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">Jadikan {{ $alias }} Favorit?</div>
                <label for="apresiasi-catatan-{{ $app->id }}">Kenapa favorit?</label>
                <textarea name="apresiasi_catatan" id="apresiasi-catatan-{{ $app->id }}" rows="3" maxlength="1000" placeholder="mis. cocok peran bapak-bapak kantoran, on time" style="width: 100%; margin-bottom: 12px;"></textarea>
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                    <button type="submit" class="btn btn-sm btn-brand">Simpan Favorit</button>
                </div>
            </form>
        </dialog>
    @endif

    <dialog id="breakdown-dialog-{{ $app->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 440px; width: 90%;">
        <form method="POST" action="{{ route('admin.applications.breakdown', $app) }}" style="padding: 18px;">
            @csrf @method('PATCH')
            <div style="font-size: 15px; font-weight: 600; margin-bottom: 12px;">Breakdown: {{ $ex->user->username ?? 'Extras' }}</div>
            <div style="margin-bottom: 10px;">
                <label>Nama Karakter / Peran</label>
                <input type="text" name="karakter" value="{{ old('karakter', $app->karakter) }}" placeholder="misal: Preman 1 / Teman Kampus" style="width: 100%;">
            </div>
            <div class="form-row" style="margin-bottom: 10px;">
                <div>
                    <label>Jam Callingan Extras</label>
                    <input type="time" name="jam_callingan" value="{{ old('jam_callingan', $app->jam_callingan) }}" style="width: 100%;">
                </div>
                <div>
                    <label>Tipe Kontinuitas</label>
                    <select name="tipe_continuity" style="width: 100%;">
                        <option value="free" @selected($app->tipe_continuity === 'free')>Bebas</option>
                        <option value="continuity" @selected($app->tipe_continuity === 'continuity')>Continuity</option>
                    </select>
                </div>
            </div>
            <div style="margin-bottom: 12px;">
                <label>Keterangan Scene</label>
                <input type="text" name="keterangan_scene" value="{{ old('keterangan_scene', $app->keterangan_scene) }}" placeholder="misal: Scene 12-14 warung kopi, baju casual" style="width: 100%;">
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
                <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">Batalkan {{ $alias }}?</div>
                <textarea name="alasan" rows="3" required placeholder="Alasan pembatalan" style="width: 100%; margin-bottom: 12px;"></textarea>
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger-outline">Batalkan Aplikasi</button>
                </div>
            </form>
        </dialog>
    @endif

    @if ($bisaCatatan)
        <dialog id="catatan-dialog-{{ $app->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 360px; width: 90%;">
            <form method="POST" action="{{ route('admin.applications.catatan', $app) }}" style="padding: 18px;">
                @csrf
                <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">Catatan Lapangan: {{ $alias }}</div>
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
@endforeach

<x-pagination-bar :paginator="$applicants" :pilihan="\App\Support\PerHalaman::KARTU" />
</div>

@endsection
