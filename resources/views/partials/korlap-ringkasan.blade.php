@php
    $absensi = fn ($projectId, $tanggalId) => route('admin.attendance.index', ['project' => $projectId, 'tanggal' => $tanggalId], false);
    $tag = $masuk ? 'button' : 'a';
    $klik = fn ($url) => $masuk ? 'type="submit" form="mon-masuk" name="ke" value="'.e($url).'"' : 'href="'.e($url).'"';

    $statKorlap = $statKorlap ?? [
        'proyek_aktif_count' => ($hari['Hari ini'] ?? collect())->concat($hari['Besok'] ?? collect())->count(),
        'total_panggilan' => (int) ($hari['Hari ini'] ?? collect())->concat($hari['Besok'] ?? collect())->sum(fn ($sd) => $sd->rekap['total'] ?? 0),
        'total_hadir' => (int) ($hari['Hari ini'] ?? collect())->concat($hari['Besok'] ?? collect())->sum(fn ($sd) => $sd->rekap['hadir'] ?? 0),
        'total_menunggu_val' => (int) ($hari['Hari ini'] ?? collect())->concat($hari['Besok'] ?? collect())->sum(fn ($sd) => $sd->rekap['menunggu_validasi'] ?? 0),
        'total_tidak_hadir' => (int) ($hari['Hari ini'] ?? collect())->concat($hari['Besok'] ?? collect())->sum(fn ($sd) => $sd->rekap['tidak_hadir'] ?? 0),
        'total_belum_absen' => (int) ($hari['Hari ini'] ?? collect())->concat($hari['Besok'] ?? collect())->sum(fn ($sd) => $sd->rekap['belum'] ?? 0),
        'tingkat_kehadiran' => (($hari['Hari ini'] ?? collect())->concat($hari['Besok'] ?? collect())->sum(fn ($sd) => $sd->rekap['total'] ?? 0) > 0)
            ? (int) round((($hari['Hari ini'] ?? collect())->concat($hari['Besok'] ?? collect())->sum(fn ($sd) => $sd->rekap['hadir'] ?? 0) / ($hari['Hari ini'] ?? collect())->concat($hari['Besok'] ?? collect())->sum(fn ($sd) => $sd->rekap['total'] ?? 0)) * 100)
            : 0,
        'menunggu_count' => count($menunggu ?? []),
        'catatan_count' => count($catatan ?? []),
        'catatan_sanksi' => collect($catatan ?? [])->where('jenis', 'sanksi')->count(),
        'catatan_info' => collect($catatan ?? [])->where('jenis', '!=', 'sanksi')->count(),
    ];
@endphp

{{-- 4 Kartu Metrik Lapangan Korlap --}}
<div class="dash-pulse-strip">
    <div class="dash-stat-card" tabindex="0">
        <div>
            <div style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                <i class="ti ti-movie"></i> Proyek Lapangan Aktif
            </div>
            <div style="font-size: 22px; font-weight: 700; color: var(--text-primary); margin: 4px 0 2px;">
                {{ $statKorlap['proyek_aktif_count'] }} Jadwal
            </div>
            <div style="font-size: 12px; color: var(--text-secondary);">
                Hari ini &amp; besok ditugaskan
            </div>
        </div>
    </div>

    <div class="dash-stat-card" tabindex="0">
        <div>
            <div style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                <i class="ti ti-user-check"></i> Tingkat Kehadiran
            </div>
            <div style="font-size: 22px; font-weight: 700; color: var(--text-primary); margin: 4px 0 2px;">
                {{ $statKorlap['tingkat_kehadiran'] }}%
            </div>
            <div class="dash-segmented-bar">
                <div class="seg" style="width: {{ $statKorlap['tingkat_kehadiran'] }}%; background: var(--accent, #15803D);"></div>
                <div class="seg" style="width: {{ 100 - $statKorlap['tingkat_kehadiran'] }}%; background: var(--surface-2, rgba(0,0,0,0.06));"></div>
            </div>
            <div style="font-size: 12px; color: var(--text-secondary);">
                {{ $statKorlap['total_hadir'] }}/{{ $statKorlap['total_panggilan'] }} talent hadir
            </div>
        </div>
        <span class="info-hint"><i class="ti ti-info-circle"></i></span>
        <div class="dash-hover-popover">
            <div style="font-weight: 700; font-size: 12px; margin-bottom: 6px; color: var(--text-primary);">Rincian Kehadiran Lapangan</div>
            <div class="popover-row"><span>Hadir Tervalidasi</span><strong>{{ $statKorlap['total_hadir'] }}</strong></div>
            <div class="popover-row"><span>Menunggu Validasi</span><strong>{{ $statKorlap['total_menunggu_val'] }}</strong></div>
            <div class="popover-row"><span>Tidak Hadir</span><strong>{{ $statKorlap['total_tidak_hadir'] }}</strong></div>
            <div class="popover-row"><span>Belum Diabsen</span><strong>{{ $statKorlap['total_belum_absen'] }}</strong></div>
        </div>
    </div>

    <div class="dash-stat-card" tabindex="0" style="{{ $statKorlap['menunggu_count'] > 0 ? 'border-color: var(--warning, #D97706);' : '' }}">
        <div>
            <div style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                <i class="ti ti-camera"></i> Antrean Validasi Selfie
            </div>
            <div style="font-size: 22px; font-weight: 700; color: {{ $statKorlap['menunggu_count'] > 0 ? 'var(--warning, #D97706)' : 'var(--text-primary)' }}; margin: 4px 0 2px;">
                {{ $statKorlap['menunggu_count'] }}
            </div>
            <div style="font-size: 12px; color: var(--text-secondary);">
                {{ $statKorlap['menunggu_count'] > 0 ? 'Perlu diverifikasi sekarang' : 'Semua presensi beres' }}
            </div>
        </div>
    </div>

    <div class="dash-stat-card" tabindex="0">
        <div>
            <div style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                <i class="ti ti-notes"></i> Catatan Lapangan
            </div>
            <div style="font-size: 22px; font-weight: 700; color: var(--text-primary); margin: 4px 0 2px;">
                {{ $statKorlap['catatan_count'] }}
            </div>
            <div style="font-size: 12px; color: var(--text-secondary);">
                {{ $statKorlap['catatan_sanksi'] }} Peringatan/Sanksi · {{ $statKorlap['catatan_info'] }} Info
            </div>
        </div>
        <span class="info-hint"><i class="ti ti-info-circle"></i></span>
        <div class="dash-hover-popover">
            <div style="font-weight: 700; font-size: 12px; margin-bottom: 6px; color: var(--text-primary);">Distribusi Catatan Lapangan</div>
            <div class="popover-row"><span>Peringatan / Sanksi</span><strong>{{ $statKorlap['catatan_sanksi'] }}</strong></div>
            <div class="popover-row"><span>Informasi / Catatan Umum</span><strong>{{ $statKorlap['catatan_info'] }}</strong></div>
        </div>
    </div>
</div>

<div class="mon-dua">
    <div class="card">
        <div class="card-title">Shooting hari ini &amp; besok</div>
        @foreach ($hari as $judul => $jadwal)
            <div style="font-size: 13px; font-weight: 600; margin: {{ $loop->first ? '0' : '14px' }} 0 4px;">
                {{ $judul }} <span class="dash-sub">&middot; {{ ($loop->first ? today() : today()->addDay())->translatedFormat('l, d M Y') }}</span>
            </div>
            @forelse ($jadwal as $sd)
                @php
                    $r = $sd->rekap;
                    $pct = fn ($n) => $r['total'] ? round($n / $r['total'] * 100, 1) : 0;
                    $korlap = $sd->castingProject->adminAssignments->filter(fn ($a) => $a->user?->isKorlap())->map(fn ($a) => $a->user->name);
                @endphp
                <{{ $tag }} {!! $klik($absensi($sd->casting_project_id, $sd->id)) !!} class="dash-row mon-row" data-shooting="{{ $sd->id }}">
                    <div style="min-width: 0; flex: 1;">
                        <strong>{{ $sd->castingProject->nama_produksi }}</strong>
                        <div class="dash-sub">
                            <i class="ti ti-map-pin"></i> {{ $sd->lokasi ?: 'Lokasi belum diisi' }}
                            &bull; <i class="ti ti-clock"></i> {{ $sd->jam_mulai ? substr($sd->jam_mulai, 0, 5) : '--:--' }}{{ $sd->jam_selesai ? '–'.substr($sd->jam_selesai, 0, 5) : '' }}
                            &bull; Korlap: {{ $korlap->isNotEmpty() ? $korlap->join(', ') : 'belum ditugaskan' }}
                        </div>
                        <div style="font-size: 13px; margin-top: 4px;">
                            <strong>{{ $r['hadir'] }}/{{ $r['total'] }} hadir</strong> &middot; {{ $r['menunggu_validasi'] }} menunggu validasi &middot; {{ $r['tidak_hadir'] }} tidak hadir
                            @if ($r['belum'])<span class="dash-sub">&middot; {{ $r['belum'] }} belum diabsen</span>@endif
                        </div>
                        <div class="mon-bar" aria-hidden="true">
                            <span class="is-hadir" style="width: {{ $pct($r['hadir']) }}%;"></span>
                            <span class="is-tunggu" style="width: {{ $pct($r['menunggu_validasi']) }}%;"></span>
                            <span class="is-absen" style="width: {{ $pct($r['tidak_hadir']) }}%;"></span>
                        </div>
                    </div>
                </{{ $tag }}>
                @unless ($masuk)
                    <div style="padding: 0 0 10px; display: flex; justify-content: flex-end; gap: 8px;">
                        <a href="{{ route('admin.attendance.index', ['project' => $sd->casting_project_id, 'tanggal' => $sd->id]) }}" class="btn btn-sm btn-brand">
                            <i class="ti ti-clipboard-check"></i> Kelola Absensi
                        </a>
                        <a href="{{ route('admin.callsheet.show', [$sd->castingProject, $sd->tanggal->toDateString()]) }}" class="btn btn-sm">
                            <i class="ti ti-file-text"></i> Callsheet
                        </a>
                    </div>
                @endunless
            @empty
                <div class="dash-sub" style="padding: 8px 0;">
                    Tidak ada shooting {{ strtolower($judul) }}.
                    @if ($loop->first && $terdekat)
                        Shooting terdekat: <strong>{{ $terdekat->castingProject?->nama_produksi }}</strong>, {{ $terdekat->tanggal->translatedFormat('l, d M Y') }}.
                    @endif
                </div>
            @endforelse
        @endforeach
    </div>

    <div style="display: flex; flex-direction: column; gap: 16px; min-width: 0;">
        <div class="card" style="margin: 0;">
            <div class="card-title">Menunggu validasi @if ($menunggu->isNotEmpty())<span class="badge badge-netral" style="margin-left: 8px;">{{ $menunggu->count() }}</span>@endif</div>
            @forelse ($menunggu as $a)
                <{{ $tag }} {!! $klik($absensi($a->projectApplication?->casting_project_id, $a->event_shooting_date_id).'#app-'.$a->project_application_id) !!} class="dash-row mon-row" style="justify-content: flex-start; flex-wrap: nowrap; gap: 10px; align-items: center;">
                    @if ($a->foto_path)
                        <img src="{{ route('admin.absensi.foto', $a) }}" alt="Selfie" class="mon-foto" loading="lazy">
                    @else
                        <span class="mon-foto" style="display: flex; align-items: center; justify-content: center; color: var(--text-muted);"><i class="ti ti-user"></i></span>
                    @endif
                    <div style="min-width: 0; flex: 1;">
                        <strong>{{ $a->projectApplication?->extras?->user?->name ?? 'Extras' }}</strong>
                        <div class="dash-sub">{{ $a->projectApplication?->castingProject?->nama_produksi }}</div>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px; flex-shrink: 0;">
                        <span class="dash-sub" style="white-space: nowrap;">{{ $a->created_at->format('H:i') }} &middot; {{ $a->created_at->translatedFormat('d M') }}</span>
                        <span class="btn btn-sm btn-tonal" style="font-size: var(--fs-xs); padding: 2px 8px; pointer-events: none;">Buka &amp; Validasi Absensi <i class="ti ti-arrow-right"></i></span>
                    </div>
                </{{ $tag }}>
            @empty
                <div class="dash-aman" role="status"><i class="ti ti-circle-check"></i> Tidak ada selfie yang menunggu validasi.</div>
            @endforelse
        </div>

        <div class="card" style="margin: 0;">
            <div class="card-title">Catatan lapangan terbaru</div>
            @forelse ($catatan as $c)
                <div class="dash-row">
                    <div style="min-width: 0; flex: 1;">
                        <span class="badge {{ $c->jenis === 'sanksi' ? 'badge-tolak' : 'badge-info' }}">{{ ucfirst($c->jenis) }}</span>
                        {{ \Illuminate\Support\Str::limit($c->isi, 90) }}
                        <div class="dash-sub">{{ $c->korlap?->name ?? 'Staf' }} <i class="ti ti-arrow-right"></i> {{ $c->projectApplication?->extras?->user?->name ?? 'Extras' }} &bull; {{ $c->projectApplication?->castingProject?->nama_produksi }} &bull; {{ $c->created_at->diffForHumans() }}</div>
                    </div>
                </div>
            @empty
                <div class="dash-sub" style="padding: 8px 0;">Belum ada catatan lapangan.</div>
            @endforelse
        </div>
    </div>
</div>
