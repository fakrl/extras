@push('styles')
<style>
.mon-kepala { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
.mon-kartu { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 16px; }
.mon-kartu .mon-btn:last-child:nth-child(odd) { grid-column: span 2; }
@media (min-width: 861px) {
    .mon-kartu { grid-template-columns: repeat(5, 1fr); }
    .mon-kartu .mon-btn:last-child:nth-child(odd) { grid-column: auto; }
}
.mon-btn { display: flex; flex-direction: column; justify-content: space-between; width: 100%; text-align: left; font: inherit; color: inherit; cursor: pointer; border: 1px solid var(--border-color); }
.mon-btn:hover { border-color: var(--accent); }
.mon-btn.is-ada { border-color: var(--accent-strong); }
.mon-btn.is-ada .metric-value { color: var(--accent-strong); }
.mon-dua { display: grid; gap: 16px; align-items: start; }
.mon-dua > .card { margin: 0; min-width: 0; }
@media (min-width: 1100px) { .mon-dua { grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr); } }
.mon-row { width: 100%; background: none; border: none; border-bottom: 1px solid var(--border-color); text-align: left; font-family: inherit; font-size: 13.5px; color: inherit; cursor: pointer; }
.mon-row:last-child { border-bottom: none; }
.mon-row:hover { color: var(--accent); }
.mon-foto { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border-color); flex-shrink: 0; }
.mon-bar { display: flex; height: 6px; border-radius: 999px; overflow: hidden; background: var(--border-color); margin-top: 6px; }
.mon-bar > span { display: block; }
.mon-bar .is-hadir { background: var(--accent); }
.mon-bar .is-tunggu { background: #eab308; }
.mon-bar .is-absen { background: var(--danger, #d9534f); }
</style>
@endpush
