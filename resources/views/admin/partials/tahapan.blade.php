@php
    $monitor = $monitor ?? false;
    $tabAwal = collect($tahapan)->sortByDesc(fn ($t) => [$t['perlu'], $t['jumlah'] > 0])->keys()->first();
@endphp
<div class="step-bar-wrap">
    <div class="step-bar tahap-bar" role="tablist" aria-label="Tahapan partisipasi kandidat">
        @foreach ($tahapan as $key => $t)
            <button type="button" role="tab" class="step-bar-item tahap-tab {{ $key === $tabAwal ? 'is-active' : '' }}" id="tahap-tab-{{ $key }}" aria-controls="tahap-panel-{{ $key }}" aria-selected="{{ $key === $tabAwal ? 'true' : 'false' }}" tabindex="{{ $key === $tabAwal ? 0 : -1 }}" data-tahap="{{ $key }}">
                <span class="step-bar-circle">{{ $t['jumlah'] }}</span>
                <span class="step-bar-label">{{ $t['label'] }}</span>
                @if ($t['perlu'])
                    <span class="tahap-perlu">{{ $t['perlu'] }} perlu kamu</span>
                @endif
            </button>
            @if (! $loop->last)
                <div class="step-bar-line"></div>
            @endif
        @endforeach
    </div>
</div>

@foreach ($tahapan as $key => $t)
    <div role="tabpanel" class="tahap-panel" id="tahap-panel-{{ $key }}" aria-labelledby="tahap-tab-{{ $key }}" @if ($key !== $tabAwal) hidden @endif>
        @forelse ($t['daftar'] as $i)
            @php $ex = $i['app']->extras; $u = $ex?->user; @endphp
            <div class="tahap-row {{ $i['perlu'] ? 'is-perlu' : '' }}" data-app="{{ $i['app']->id }}">
                @if ($ex?->foto_profil_path)
                    <img class="tahap-foto" src="{{ route('extras.media.foto', $ex) }}" alt="" loading="lazy">
                @else
                    <span class="tahap-foto" aria-hidden="true">{{ strtoupper(mb_substr($u->username ?? '?', 0, 2)) }}</span>
                @endif
                <div class="tahap-isi">
                    <strong>{{ '@'.($u->username ?? '-') }}</strong>
                    <span class="dash-sub">{{ $i['app']->castingProject?->kode_proyek }} &middot; {{ $i['app']->castingProjectClass?->nama_kelas ?? $i['app']->karakter ?? '-' }}</span>
                    <div class="tahap-aksi">{{ $i['teks'] }}</div>
                </div>
                <div class="tahap-kanan">
                    <span class="dash-sub">{{ $i['sejak']?->diffForHumans() }}</span>
                    @if ($monitor)
                        <button type="submit" form="mon-masuk" name="ke" value="{{ $i['lineup'] }}" class="btn btn-sm">Buka</button>
                    @else
                        <a href="{{ $i['url'] }}" class="btn btn-sm {{ $i['perlu'] ? 'btn-brand' : '' }}">{{ $i['tombol'] }}</a>
                    @endif
                </div>
            </div>
        @empty
            <p class="dash-sub tahap-kosong">Nggak ada kandidat di tahap ini.</p>
        @endforelse
        @if ($monitor)
            <button type="submit" form="mon-masuk" name="ke" value="{{ $t['url'] }}" class="tahap-semua">Lihat semua di tahap ini &rarr;</button>
        @else
            <a href="{{ $t['url'] }}" class="tahap-semua">Lihat semua di tahap ini &rarr;</a>
        @endif
    </div>
@endforeach

@push('scripts')
<script>
(function () {
    var tabs = [].slice.call(document.querySelectorAll('.tahap-tab'));
    var pilih = function (t) {
        tabs.forEach(function (x) {
            var aktif = x === t;
            x.setAttribute('aria-selected', aktif);
            x.classList.toggle('is-active', aktif);
            x.tabIndex = aktif ? 0 : -1;
            document.getElementById(x.getAttribute('aria-controls')).hidden = !aktif;
        });
    };
    var awal = tabs.filter(function (x) { return x.classList.contains('is-active'); })[0];
    var wrap = awal && awal.closest('.step-bar-wrap');
    if (wrap) wrap.scrollLeft = awal.offsetLeft - wrap.offsetLeft - 16;
    tabs.forEach(function (t, i) {
        t.addEventListener('click', function () { pilih(t); });
        t.addEventListener('keydown', function (e) {
            var n = { ArrowRight: i + 1, ArrowLeft: i - 1, Home: 0, End: tabs.length - 1 }[e.key];
            if (n === undefined) return;
            e.preventDefault();
            var t2 = tabs[(n + tabs.length) % tabs.length];
            pilih(t2);
            t2.focus();
        });
    });
}());
</script>
@endpush
