@props(['dari', 'sampai', 'preset', 'action' => null])

@php
    $semua = \App\Support\Periode::MUNDUR + \App\Support\Periode::MAJU;
    $label = \App\Support\Periode::label($dari, $sampai, $preset);
    $pid = 'fp'.uniqid();
    $bawa = collect(request()->except(['periode', 'dari', 'sampai']))->filter(fn ($v) => is_scalar($v));
@endphp

@once
@push('styles')
<style>
.fp-trigger { display: inline-flex; align-items: center; gap: 6px; }
.fp-custom { margin-top: 10px; }
</style>
@endpush
@push('scripts')
<script>
document.addEventListener('change', function (e) {
    if (!e.target.matches('[data-fp-select]')) return;
    var panel = e.target.closest('[data-k=filter-periode]');
    var custom = panel.querySelector('[data-fp-custom]');
    if (e.target.value === 'custom') { custom.hidden = false; return; }
    e.target.form.submit();
});
</script>
@endpush
@endonce

<details class="fpanel" data-k="filter-periode">
    <summary class="btn fp-trigger"><i class="ti ti-calendar"></i> {{ $label }}</summary>
    <div class="fpanel-isi" role="group" aria-label="Pilih periode">
        <div class="fpanel-body">
            <form method="GET" action="{{ $action ?? url()->current() }}">
                @foreach ($bawa as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <label class="fpanel-label" for="{{ $pid }}">Periode</label>
                <select name="periode" id="{{ $pid }}" data-fp-select>
                    @foreach ($semua as $val => $lbl)
                        <option value="{{ $val }}" @selected($preset === $val)>{{ $lbl }}</option>
                    @endforeach
                    <option value="custom" @selected(! $preset)>Pilih tanggal…</option>
                </select>

                <div class="fpanel-dua fp-custom" data-fp-custom @if ($preset) hidden @endif>
                    <input type="date" name="dari" value="{{ $dari->format('Y-m-d') }}" aria-label="Dari tanggal">
                    <span>&ndash;</span>
                    <input type="date" name="sampai" value="{{ $sampai->format('Y-m-d') }}" aria-label="Sampai tanggal">
                    <button type="submit" class="btn btn-brand">Terapkan</button>
                </div>
            </form>
        </div>
    </div>
</details>
