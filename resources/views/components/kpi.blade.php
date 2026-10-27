@props(['label', 'angka', 'href', 'sub' => null, 'mendesak' => false, 'pertumbuhan' => null])

@once
@push('styles')
<style>
.kpi-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 16px; }
@media (min-width: 861px) { .kpi-grid { grid-template-columns: repeat(4, 1fr); } }
.kpi-card { display: block; min-height: 44px; padding: 14px; border: 1px solid var(--border-color); border-radius: 10px; color: inherit; text-decoration: none; }
.kpi-card:hover { border-color: var(--accent); }
.kpi-label { font-size: 12.5px; color: var(--text-secondary); }
.kpi-angka { font-size: var(--fs-lg, 22px); font-weight: 700; margin-top: 2px; }
.kpi-angka.is-mendesak { color: var(--warning, #b45309); }
.kpi-sub { font-size: 11.5px; color: var(--text-muted); margin-top: 2px; }
</style>
@endpush
@endonce

<a href="{{ $href }}" class="kpi-card">
    <div class="kpi-label">{{ $label }}</div>
    <div class="kpi-angka {{ $mendesak ? 'is-mendesak' : '' }}">{{ $angka }}</div>
    @if ($sub)<div class="kpi-sub">{{ $sub }}</div>@endif
    @if ($pertumbuhan !== null)
        <div class="kpi-sub">{{ $pertumbuhan >= 0 ? '▲' : '▼' }} {{ abs($pertumbuhan) }}% vs periode sebelumnya</div>
    @endif
</a>
