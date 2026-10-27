@props(['dari', 'sampai', 'preset', 'action' => null])

@php
    $label = \App\Support\Periode::label($dari, $sampai, $preset);
    $bawa = collect(request()->except(['periode', 'dari', 'sampai']))->filter(fn ($v) => is_scalar($v));
    $base = $action ?? url()->current();
    $tautan = fn (string $periode) => $base.'?'.http_build_query($bawa->toArray() + ['periode' => $periode]);
@endphp

@once
@push('styles')
<style>
.fp-trigger { display: inline-flex; align-items: center; gap: 6px; }
.fp-menu { padding: 6px; min-width: 200px; }
.fp-opsi { display: block; width: 100%; text-align: left; padding: 9px 10px; border-radius: 6px; font: inherit; color: inherit; background: none; border: none; cursor: pointer; }
.fp-opsi:hover, .fp-opsi[aria-current=true] { background: var(--bg-hover, rgba(0,0,0,.05)); }
.fp-opsi[aria-current=true] { font-weight: 600; color: var(--accent); }
.fp-custom { margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--border-color); }
.fp-custom[hidden] { display: none; }
</style>
@endpush
@push('scripts')
<script>
document.addEventListener('click', function (e) {
    var toggle = e.target.closest('[data-fp-kustom-toggle]');
    if (!toggle) return;
    e.preventDefault();
    var panel = toggle.closest('[data-k=filter-periode]').querySelector('[data-fp-custom]');
    panel.hidden = !panel.hidden;
});
</script>
@endpush
@endonce

<details class="fpanel" data-k="filter-periode">
    <summary class="btn fp-trigger"><i class="ti ti-calendar"></i> {{ $label }} <i class="ti ti-chevron-down"></i></summary>
    <div class="fpanel-isi" role="group" aria-label="Pilih rentang tanggal">
        <div class="fpanel-body fp-menu">
            @foreach (\App\Support\Periode::PRESET as $val => $lbl)
                <a href="{{ $tautan($val) }}" class="fp-opsi" aria-current="{{ $preset === $val ? 'true' : 'false' }}">{{ $lbl }}</a>
            @endforeach
            <button type="button" class="fp-opsi" data-fp-kustom-toggle aria-current="{{ ! $preset ? 'true' : 'false' }}">Kustom&hellip;</button>

            <form method="GET" action="{{ $base }}" class="fpanel-dua fp-custom" data-fp-custom @if ($preset) hidden @endif>
                @foreach ($bawa as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <input type="date" name="dari" value="{{ $dari->format('Y-m-d') }}" aria-label="Dari tanggal">
                <span>&ndash;</span>
                <input type="date" name="sampai" value="{{ $sampai->format('Y-m-d') }}" aria-label="Sampai tanggal">
                <button type="submit" class="btn btn-brand">Terapkan</button>
            </form>
        </div>
    </div>
</details>
