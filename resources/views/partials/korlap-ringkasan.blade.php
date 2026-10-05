@php
    $absensi = fn ($projectId, $tanggalId) => route('admin.attendance.index', ['project' => $projectId, 'tanggal' => $tanggalId], false);
    $tag = $masuk ? 'button' : 'a';
    $klik = fn ($url) => $masuk ? 'type="submit" form="mon-masuk" name="ke" value="'.e($url).'"' : 'href="'.e($url).'"';
@endphp
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
            @empty
                <div class="dash-sub" style="padding: 8px 0;">
                    Tidak ada shooting {{ strtolower($judul) }}.
                    @if ($loop->first && $terdekat)
                        Shooting terdekat: <strong>{{ $terdekat->tanggal->translatedFormat('l, d M Y') }}</strong>.
                    @endif
                </div>
            @endforelse
        @endforeach
    </div>

    <div style="display: flex; flex-direction: column; gap: 16px; min-width: 0;">
        <div class="card" style="margin: 0;">
            <div class="card-title">Menunggu validasi @if ($menunggu->isNotEmpty())<span class="badge badge-pending" style="margin-left: 8px;">{{ $menunggu->count() }}</span>@endif</div>
            @forelse ($menunggu as $a)
                <{{ $tag }} {!! $klik($absensi($a->projectApplication?->casting_project_id, $a->event_shooting_date_id).'#app-'.$a->project_application_id) !!} class="dash-row mon-row" style="justify-content: flex-start; flex-wrap: nowrap;">
                    @if ($a->foto_path)
                        <img src="{{ route('admin.absensi.foto', $a) }}" alt="Selfie" class="mon-foto" loading="lazy">
                    @else
                        <span class="mon-foto" style="display: flex; align-items: center; justify-content: center; color: var(--text-muted);"><i class="ti ti-user"></i></span>
                    @endif
                    <div style="min-width: 0; flex: 1;">
                        <strong>{{ $a->projectApplication?->extras?->user?->name ?? 'Extras' }}</strong>
                        <div class="dash-sub">{{ $a->projectApplication?->castingProject?->nama_produksi }}</div>
                    </div>
                    <span class="dash-sub" style="white-space: nowrap;">{{ $a->created_at->format('H:i') }} &middot; {{ $a->created_at->translatedFormat('d M') }}</span>
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
                        <div class="dash-sub">{{ $c->korlap?->name ?? 'Staf' }} &rarr; {{ $c->projectApplication?->extras?->user?->name ?? 'Extras' }} &bull; {{ $c->projectApplication?->castingProject?->nama_produksi }} &bull; {{ $c->created_at->diffForHumans() }}</div>
                    </div>
                </div>
            @empty
                <div class="dash-sub" style="padding: 8px 0;">Belum ada catatan lapangan.</div>
            @endforelse
        </div>
    </div>
</div>
