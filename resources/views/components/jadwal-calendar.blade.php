@props(['events', 'compact' => false, 'bulan' => null, 'detail' => false])

@php
    $now = \Carbon\Carbon::now();
    $targetBulan = $compact ? $now : \Carbon\Carbon::createFromFormat('Y-m', $bulan ?? $now->format('Y-m'));
    $startOfMonth = $targetBulan->copy()->startOfMonth();
    $endOfMonth = $targetBulan->copy()->endOfMonth();
    $startOfGrid = $startOfMonth->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
    $endOfGrid = $endOfMonth->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
    $eventsByDate = $events->groupBy(fn($e) => \Carbon\Carbon::parse($e->tanggal)->format('Y-m-d'));
    $calId = uniqid('cal');
    $prevMonth = $targetBulan->copy()->subMonth()->format('Y-m');
    $nextMonth = $targetBulan->copy()->addMonth()->format('Y-m');
    $hasEventsThisMonth = false;
    $cur = $startOfGrid->copy();
    while ($cur->lte($endOfGrid)) {
        if ($eventsByDate->has($cur->format('Y-m-d'))) { $hasEventsThisMonth = true; break; }
        $cur->addDay();
    }
@endphp

@once
@push('styles')
<style>
.jadwal-cal-wrap { display: grid; grid-template-columns: 1fr; gap: 16px; }
.jadwal-cal-wrap.has-detail { grid-template-columns: 1fr 260px; }
@media (max-width: 640px) { .jadwal-cal-wrap.has-detail { grid-template-columns: 1fr; } }
.jadwal-cal-compact .jadwal-cal-wrap.has-detail { grid-template-columns: 1fr; }
.cal-header { display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; font-size: var(--fs-xs); font-weight: 600; color: var(--text-secondary); margin-bottom: 4px; }
.cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; }
.cal-day { min-height: 32px; border-radius: 6px; display: flex; flex-direction: column; align-items: center; padding: 4px 2px; font-size: 12px; cursor: default; }
.cal-day.has-event { cursor: pointer; }
.cal-day.has-event:hover, .cal-day.has-event.selected { background: color-mix(in srgb, var(--accent) 15%, transparent); }
.cal-day.is-today { font-weight: 700; color: var(--accent); }
.cal-day.out-of-month { color: var(--text-muted); opacity: 0.4; }
.cal-dot { width: 5px; height: 5px; border-radius: 50%; background: var(--accent); margin-top: 2px; }
.cal-detail-panel { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px; font-size: 13px; display: none; }
.cal-event-item + .cal-event-item { margin-top: 10px; border-top: 1px solid var(--border-color); padding-top: 10px; }
.cal-meta { color: var(--text-secondary); font-size: 12px; }
.cal-nav { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; font-size: 13px; font-weight: 600; }
.cal-nav a { color: var(--text-secondary); text-decoration: none; font-size: 18px; line-height: 1; padding: 2px 6px; border-radius: 4px; }
.cal-nav a:hover { background: var(--bg-nav-active); }
.cal-aksi { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px; }
.cal-aksi .btn { min-height: 32px; padding: 0 10px; font-size: 12px; }
button.cal-day { border: none; margin: 0; font: inherit; color: inherit; width: 100%; background: transparent; }
</style>
@endpush
@endonce

@once
<script>
function calClick(el, calId) {
    if (!el.dataset.events) return;
    var events = JSON.parse(el.dataset.events);
    var panel = document.getElementById('cal-detail-' + calId);
    // lokasi/catatan diisi Client, jadi wajib di-escape sebelum masuk innerHTML
    var esc = function(v) { var d = document.createElement('div'); d.textContent = v == null ? '' : v; return d.innerHTML; };
    var escAttr = function(v) { return esc(v).replace(/"/g, '&quot;'); };
    var html = events.map(function(e) {
        return '<div class="cal-event-item"><strong>' + esc(e.nama || 'Jadwal') + '</strong>' +
               (e.jam_mulai ? '<br>' + esc(e.jam_mulai) + (e.jam_selesai ? '&ndash;' + esc(e.jam_selesai) : '') : '') +
               (e.lokasi ? '<br><span class="cal-meta">' + esc(e.lokasi) + '</span>' : '') +
               (e.catatan ? '<br><em class="cal-meta">' + esc(e.catatan) + '</em>' : '') +
               (e.extras != null ? '<br><span class="cal-meta">' + esc(e.extras) + ' Extras &middot; ' + esc(e.absensi) + '</span>' : '') +
               (e.url_proyek ? '<div class="cal-aksi"><a class="btn btn-sm" href="' + escAttr(e.url_proyek) + '">Buka proyek</a>' +
                   '<a class="btn btn-sm" href="' + escAttr(e.url_absensi) + '">Lihat absensi</a></div>' : '') +
               '</div>';
    }).join('');
    panel.innerHTML = html;
    panel.style.display = 'block';
    document.querySelectorAll('.cal-day.has-event').forEach(function(d) { d.classList.remove('selected'); });
    el.classList.add('selected');
}
</script>
@endonce

<div class="{{ $compact ? 'jadwal-cal-compact' : '' }}" style="{{ $compact ? 'max-width: 420px;' : '' }}">
    @if (!$compact)
        <div class="cal-nav">
            <a href="{{ request()->fullUrlWithQuery(['bulan' => $prevMonth]) }}">&#8249;</a>
            <span>{{ $targetBulan->translatedFormat('F Y') }}</span>
            <a href="{{ request()->fullUrlWithQuery(['bulan' => $nextMonth]) }}">&#8250;</a>
        </div>
    @else
        <div class="cal-nav">
            <span>{{ $targetBulan->translatedFormat('F Y') }}</span>
        </div>
    @endif

    <div class="jadwal-cal-wrap {{ !$compact ? 'has-detail' : '' }}" id="calwrap-{{ $calId }}">
        <div>
            <div class="cal-header">
                @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $h)
                    <div>{{ $h }}</div>
                @endforeach
            </div>
            <div class="cal-grid">
                @php $cur = $startOfGrid->copy(); @endphp
                @while ($cur->lte($endOfGrid))
                    @php
                        $dateStr = $cur->format('Y-m-d');
                        $dayEvents = $eventsByDate->get($dateStr, collect());
                        $isToday = $cur->isToday();
                        $isOutOfMonth = !$cur->between($startOfMonth, $endOfMonth);
                        $eventsData = $dayEvents->map(fn($e) => [
                            'nama' => $e->nama_produksi ?? '',
                            'lokasi' => $e->lokasi,
                            'jam_mulai' => $e->jam_mulai ? substr($e->jam_mulai, 0, 5) : null,
                            'jam_selesai' => $e->jam_selesai ? substr($e->jam_selesai, 0, 5) : null,
                            'catatan' => $e->catatan,
                            ...($detail ? [
                                'extras' => $e->jumlah_extras,
                                'absensi' => $e->absensi,
                                'url_proyek' => $e->url_proyek,
                                'url_absensi' => $e->url_absensi,
                            ] : []),
                        ])->toArray();
                    @endphp
                    @if ($dayEvents->isNotEmpty())
                        <button type="button"
                            class="cal-day has-event {{ $isToday ? 'is-today' : '' }} {{ $isOutOfMonth ? 'out-of-month' : '' }}"
                            data-events="{{ json_encode($eventsData) }}"
                            onclick="calClick(this, '{{ $calId }}')"
                            onmouseenter="calClick(this, '{{ $calId }}')"
                            aria-label="{{ $cur->translatedFormat('d F Y') }}, ada jadwal"
                        >
                            {{ $cur->day }}
                            <div class="cal-dot"></div>
                        </button>
                    @else
                        <div class="cal-day {{ $isToday ? 'is-today' : '' }} {{ $isOutOfMonth ? 'out-of-month' : '' }}">
                            {{ $cur->day }}
                        </div>
                    @endif
                    @php $cur->addDay(); @endphp
                @endwhile
            </div>
            @if (!$hasEventsThisMonth)
                <div style="text-align: center; color: var(--text-muted); font-size: 12px; margin-top: 10px;">Tidak ada jadwal bulan ini.</div>
            @endif
        </div>
        @if (!$compact)
            <div id="cal-detail-{{ $calId }}" class="cal-detail-panel"></div>
        @else
            <div id="cal-detail-{{ $calId }}" class="cal-detail-panel" style="margin-top: 8px;"></div>
        @endif
    </div>
</div>
