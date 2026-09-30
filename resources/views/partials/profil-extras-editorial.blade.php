{{-- BH.1: satu layout profil Extras. Param: $mode (pemilik|admin|client|publik), $profile, $fotoUrl, $videoUrl, $fotos (list url/alt).
     Publik: tanpa grade, tarif, tautan tambahan, tag Look & Lainnya (D22, BJ.1), kontak; usia rentang; video terkunci.
     Client (BI.1): seperti publik tapi video, usia & tag Look tampil (CLAUDE.md §5, BA.6). --}}
@php
    $publik = $mode === 'publik';
    $terbatas = $publik || $mode === 'client';
    $username = $profile->user->username ?? '';
    preg_match('/^(.*?)([._-][^._-]*|.{1,2})$/u', $username, $um);
    $tagGrup = $profile->categories->groupBy(fn ($c) => $c->grup ?: 'Lainnya');
    $usiaTag = $tagGrup->pull('Usia tampilan', collect())->pluck('nama');
    if ($publik) {
        $tagGrup->forget(['Tampilan/Look', 'Lainnya']);
    }
    $usia = $profile->usia ? ($publik ? (intdiv($profile->usia, 5) * 5).'–'.(intdiv($profile->usia, 5) * 5 + 4).' tahun' : $profile->usia.' tahun') : '-';
    $gender = ['pria' => 'Laki-laki', 'wanita' => 'Perempuan'][strtolower((string) $profile->gender)] ?? '-';
    $bahasa = collect(preg_split('/\s*[,;\/]\s*/', (string) $profile->bahasa))->filter();
    $status = $profile->statusTampil();
    $statusClass = ['Aktif' => 'badge-aktif', 'Sedang di proyek' => 'badge-pending'][$status] ?? 'badge-tolak';
    $lbId = $publik ? 'pub-lb' : 'profil-lb';
    $data = [
        'Usia' => $usia,
        'Jenis kelamin' => $gender,
        'Tinggi badan' => $profile->tinggi_badan ? $profile->tinggi_badan.' cm' : '-',
        'Ukuran baju' => $profile->ukuran_baju ?: '-',
        'Warna kulit' => $profile->warna_kulit ?: '-',
    ];
@endphp
<style>
.pe { --pe-serif: Georgia, 'Times New Roman', serif; color: var(--pe-fg); background: var(--pe-bg); container-type: inline-size; }
.pe-app { --pe-bg: var(--bg-card); --pe-fg: var(--text-primary); --pe-muted: var(--text-secondary); --pe-accent: var(--accent-strong); --pe-accent-bg: var(--accent); --pe-accent-on: var(--accent-on); --pe-line: var(--border-color); --pe-soft: var(--bg-card-hover); border: 1px solid var(--pe-line); border-radius: var(--radius-lg); overflow: hidden; }
.pe-publik { --pe-bg: var(--hp-bg); --pe-fg: var(--hp-fg); --pe-muted: var(--hp-muted); --pe-accent: var(--hp-accent); --pe-accent-bg: var(--hp-accent); --pe-accent-on: var(--hp-accent-on); --pe-line: var(--hp-line); --pe-soft: var(--hp-card); }
.pe-sec { border-bottom: 1px solid var(--pe-line); }
.pe-sec:last-child { border-bottom: none; }
.pe-pad { padding: 20px; }
.pe-label { font-size: 12px; text-transform: uppercase; letter-spacing: .14em; color: var(--pe-muted); margin: 0 0 8px; }
.pe-num { font-size: 12px; text-transform: uppercase; letter-spacing: .18em; color: var(--pe-accent); font-weight: 600; margin: 0 0 28px; }
.pe-serif { font-family: var(--pe-serif); font-weight: 400; letter-spacing: -.02em; }
.pe-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 24px; }
.pe-head .pe-num { margin: 0; }
.pe-actions { display: flex; gap: 8px; }
.pe-act { display: inline-flex; align-items: center; gap: 8px; min-height: 44px; padding: 0 14px; border: 1px solid var(--pe-line); border-radius: 8px; background: transparent; color: var(--pe-fg); font: inherit; font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; }
.pe-act:hover { border-color: var(--pe-accent); color: var(--pe-accent); }
.pe-hero { display: grid; gap: 28px; }
.pe-foto { position: relative; aspect-ratio: .82; overflow: hidden; background: var(--pe-soft); display: flex; align-items: center; justify-content: center; }
.pe-foto img { width: 100%; height: 100%; object-fit: cover; display: block; filter: grayscale(1); transition: filter .4s; }
.pe-foto:hover img, .pe-foto:focus-within img { filter: none; }
.pe-foto > i { font-size: 40px; color: var(--pe-muted); }
.pe-badge { position: absolute; left: 14px; bottom: 14px; padding: 8px 12px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .12em; background: var(--pe-accent-bg); color: var(--pe-accent-on); }
.pe-badge.is-off { background: var(--pe-soft); color: var(--pe-muted); }
.pe-name { font-size: clamp(3rem, 9cqi, 7.5rem); line-height: .85; letter-spacing: -.06em; margin: 0; word-break: break-word; }
.pe-name span { color: var(--pe-accent); }
.pe-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 24px; }
.pe-chip { border: 1px solid var(--pe-line); padding: 7px 12px; font-size: 12px; text-transform: uppercase; letter-spacing: .1em; color: var(--pe-muted); }
.pe-grade { display: flex; align-items: center; gap: 10px; margin-top: 28px; font-size: 12px; text-transform: uppercase; letter-spacing: .14em; color: var(--pe-muted); }
.pe-grade::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: var(--pe-accent); }
.pe-grade strong { color: var(--pe-fg); }
.pe-sub { font-size: 13px; color: var(--pe-muted); margin: 16px 0 0; }
.pe-grid2 { display: grid; }
.pe-grid2 > * + * { border-top: 1px solid var(--pe-line); }
.pe-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 24px 20px; }
.pe-val { font-size: 22px; margin: 0; }
.pe-text { font-size: 14px; line-height: 1.65; margin: 0; white-space: pre-line; }
.pe-stack > * + * { margin-top: 24px; }
.pe-exp { padding: 10px 0; border-top: 1px solid var(--pe-line); }
.pe-exp:first-of-type { border-top: 0; padding-top: 0; }
.pe-exp-judul { font-size: 18px; margin: 0; display: flex; justify-content: space-between; gap: 12px; }
.pe-exp-judul span { font-family: inherit; font-size: 12px; letter-spacing: .1em; color: var(--pe-accent); white-space: nowrap; padding-top: 4px; }
.pe-exp-ket { font-size: 13px; color: var(--pe-muted); margin: 2px 0 0; }
.pe-tags { display: flex; flex-wrap: wrap; gap: 6px; }
.pe-tag { font-size: 12px; padding: 5px 10px; border-radius: 999px; background: var(--pe-soft); color: var(--pe-fg); }
.pe-link { display: block; font-size: 14px; color: var(--pe-accent); word-break: break-all; margin-bottom: 4px; }
.pe-box { aspect-ratio: 16/9; border: 1px dashed var(--pe-line); background: var(--pe-soft); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; text-align: center; padding: 16px; }
.pe-box i { font-size: 26px; color: var(--pe-muted); }
.pe-box .pe-label { margin: 0; }
.pe-video { width: 100%; aspect-ratio: 16/9; background: #000; display: block; }
.pe-rate { font-size: clamp(2.2rem, 5cqi, 3.2rem); letter-spacing: -.04em; margin: 0; }
.pe-cta { display: inline-flex; align-items: center; gap: 10px; min-height: 48px; padding: 0 20px; margin-top: 28px; background: var(--pe-accent-bg); color: var(--pe-accent-on); font-size: 14px; font-weight: 700; text-decoration: none; border-radius: 8px; }
.pe-gal-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; margin-bottom: 20px; }
.pe-gal-head .pe-num { margin-bottom: 8px; }
.pe-gal-title { font-size: clamp(2rem, 5cqi, 3rem); margin: 0; }
.pe-gal { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
.pe-gal button { padding: 0; border: none; background: var(--pe-soft); cursor: pointer; aspect-ratio: .82; overflow: hidden; min-height: 44px; }
.pe-gal img { width: 100%; height: 100%; object-fit: cover; display: block; filter: grayscale(1); transition: filter .4s; }
.pe-gal button:hover img, .pe-gal button:focus-visible img { filter: none; }
.pe-empty { min-height: 180px; border: 1px dashed var(--pe-line); background: var(--pe-soft); display: flex; align-items: center; justify-content: center; font-size: 12px; text-transform: uppercase; letter-spacing: .14em; color: var(--pe-muted); }
@container (min-width: 700px) {
    .pe-pad { padding: 40px; }
    .pe-hero { grid-template-columns: minmax(220px, min(400px, 45%)) 1fr; gap: 56px; align-items: end; }
    .pe-grid2 { grid-template-columns: 1fr 1fr; }
    .pe-grid2 > * + * { border-top: none; border-left: 1px solid var(--pe-line); }
    .pe-gal { grid-template-columns: repeat(4, 1fr); }
    .pe-num { margin-bottom: 40px; }
}
</style>

<div class="pe {{ $publik ? 'pe-publik' : 'pe-app' }}" data-username="{{ $username }}" data-status="{{ $status }}" data-status-class="{{ $statusClass }}">
    <section class="pe-sec pe-pad">
        <div class="pe-head">
            <p class="pe-num">Profil Talent / #{{ str_pad($profile->id, 3, '0', STR_PAD_LEFT) }}</p>
            @if ($mode === 'pemilik')
                <div class="pe-actions">
                    <button type="button" id="btn-share" class="pe-act"><i class="ti ti-share"></i> Bagikan</button>
                    <a href="{{ route('extras.profile.edit') }}" class="pe-act"><i class="ti ti-edit"></i> Edit profil</a>
                </div>
            @endif
        </div>
        <div class="pe-hero">
            <div class="pe-foto" tabindex="0">
                @if ($fotoUrl)
                    <img src="{{ $fotoUrl }}" alt="Foto profil {{ $username }}">
                @else
                    <i class="ti ti-photo-off" aria-hidden="true"></i>
                @endif
                <span class="pe-badge {{ $status === 'Tidak aktif' ? 'is-off' : '' }}">{{ $status }}</span>
            </div>
            <div>
                <p class="pe-label">Extras{{ $usiaTag->isNotEmpty() ? ' · '.$usiaTag->implode(' · ') : '' }}</p>
                <h1 class="pe-name pe-serif">@if ($username){{ $um[1] ?? '' }}<span>{{ $um[2] ?? '' }}</span>@else Belum diisi @endif</h1>
                @if ($mode === 'admin')
                    <p class="pe-sub">Nama akun: {{ $profile->user->name }}</p>
                @endif
                @if ($bahasa->isNotEmpty())
                    <div class="pe-chips">
                        @foreach ($bahasa as $b)
                            <span class="pe-chip">{{ $b }}</span>
                        @endforeach
                    </div>
                @endif
                @unless ($terbatas)
                    <div class="pe-grade">@if ($profile->grade_saat_ini)<strong>Grade {{ $profile->grade_saat_ini }}</strong>@else Grade belum dinilai @endif</div>
                @endunless
            </div>
        </div>
    </section>

    <section class="pe-sec pe-grid2">
        <div class="pe-pad">
            <p class="pe-num">01 / Data diri &amp; ciri fisik</p>
            <div class="pe-fields">
                @foreach ($data as $label => $nilai)
                    <div>
                        <p class="pe-label">{{ $label }}</p>
                        <p class="pe-val pe-serif">{{ $nilai }}</p>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="pe-pad pe-stack">
            <p class="pe-num">02 / Pengalaman &amp; kemampuan</p>
            <div>
                <p class="pe-label">Pengalaman main / kerja</p>
                @forelse ($profile->pengalamanUrut() as $pg)
                    <div class="pe-exp">
                        <p class="pe-exp-judul pe-serif">{{ $pg['judul'] }}@if (! empty($pg['tahun']))<span>{{ $pg['tahun'] }}</span>@endif</p>
                        @if (! empty($pg['keterangan']))<p class="pe-exp-ket">{{ $pg['keterangan'] }}</p>@endif
                    </div>
                @empty
                    <p class="pe-text">Belum diisi</p>
                @endforelse
            </div>
            <div>
                <p class="pe-label">Bahasa</p>
                <p class="pe-val pe-serif">{{ $profile->bahasa ?: '-' }}</p>
            </div>
            @foreach ($tagGrup as $grup => $tags)
                <div>
                    <p class="pe-label">{{ $grup }}</p>
                    <div class="pe-tags">
                        @foreach ($tags as $tag)
                            <span class="pe-tag">#{{ $tag->nama }}</span>
                        @endforeach
                    </div>
                </div>
            @endforeach
            @unless ($terbatas)
                <div>
                    <p class="pe-label">Tautan tambahan</p>
                    @forelse ($profile->tautan_tambahan ?? [] as $tautan)
                        <a class="pe-link" href="{{ $tautan['url'] }}" target="_blank" rel="noopener">{{ $tautan['label'] }}: {{ $tautan['url'] }}</a>
                    @empty
                        <p class="pe-text" style="color: var(--pe-muted);">-</p>
                    @endforelse
                </div>
            @endunless
        </div>
    </section>

    <section class="pe-sec pe-grid2">
        <div class="pe-pad">
            <p class="pe-num">03 / Showreel</p>
            @if ($publik)
                <div class="pe-box"><i class="ti ti-lock" aria-hidden="true"></i><p class="pe-serif" style="font-size: 24px; margin: 0;">Video profil</p><p class="pe-label">Tersedia untuk Client &amp; Admin</p></div>
            @elseif ($videoUrl)
                <video class="pe-video" src="{{ $videoUrl }}" controls preload="metadata"></video>
            @else
                <div class="pe-box"><i class="ti ti-video-off" aria-hidden="true"></i><p class="pe-serif" style="font-size: 24px; margin: 0;">Video profil</p><p class="pe-label">Belum ada video</p></div>
            @endif
        </div>
        <div class="pe-pad">
            @if ($terbatas)
                <p class="pe-num">04 / Casting</p>
                <p class="pe-val pe-serif">{{ $publik ? 'Tertarik dengan talent ini?' : 'Nama asli & kontak dipegang JBTB' }}</p>
                <p class="pe-sub">Jadwal, tarif, dan kontrak diatur lewat tim JBTB Casting.</p>
                @if ($publik)
                <a class="pe-cta" href="https://instagram.com/jbtb.casting" target="_blank" rel="noopener">Ajak casting lewat JBTB <i class="ti ti-arrow-up-right"></i></a>
                @endif
            @else
                <p class="pe-num">04 / Tarif</p>
                <p class="pe-label">Tarif harapan</p>
                <p class="pe-rate pe-serif">{{ $profile->rate_card ? 'Rp '.number_format($profile->rate_card, 0, ',', '.') : '-' }}</p>
                <p class="pe-sub">Angka final disepakati lewat nego fee per proyek.</p>
            @endif
        </div>
    </section>

    <section class="pe-sec pe-pad">
        <div class="pe-gal-head">
            <div>
                <p class="pe-num">05 / Gallery</p>
                <h2 class="pe-gal-title pe-serif">Foto tambahan</h2>
            </div>
            <span class="pe-label" style="margin: 0;">{{ count($fotos) }} / 4 foto</span>
        </div>
        @if (count($fotos))
            <div class="pe-gal">
                @foreach ($fotos as $i => $foto)
                    <button type="button" onclick="_lbOpen('{{ $lbId }}', {{ $i }})" aria-label="Perbesar {{ $foto['alt'] }}"><img src="{{ $foto['url'] }}" alt="{{ $foto['alt'] }}" loading="lazy"></button>
                @endforeach
            </div>
            @include('partials.foto-lightbox', ['fotos' => $fotos, 'lightboxId' => $lbId, 'thumbs' => false])
        @else
            <div class="pe-empty">Belum ada foto</div>
        @endif
    </section>
</div>
