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
    $tanggalEvent = $eventsByDate->keys()->sort()->values();
    $hasEventsThisMonth = $tanggalEvent->contains(fn($k) => $k >= $startOfGrid->format('Y-m-d') && $k <= $endOfGrid->format('Y-m-d'));
    $tanggalBulanIni = $tanggalEvent->filter(fn($k) => str_starts_with($k, $targetBulan->format('Y-m')))->values();
    $defaultDate = $tanggalBulanIni->first(fn($k) => $k >= $now->format('Y-m-d')) ?? $tanggalBulanIni->last();
    $warna = fn($e) => 'hsl('.((int) $e->casting_project_id * 137 % 360).' 65% 50%)';
@endphp

@once
@push('styles')
<style>
.jadwal-cal { max-width: 360px; }
.cal-nav { display: flex; align-items: center; justify-content: space-between; min-height: 44px; margin-bottom: 4px; }
.cal-nav-title { font-size: 15px; font-weight: 700; }
.cal-nav-btn { display: flex; }
.cal-nav a { width: 44px; height: 44px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; color: var(--text-secondary); text-decoration: none; font-size: 22px; line-height: 1; }
.cal-nav a:hover { background: var(--bg-nav-active); color: var(--accent); }
.cal-header, .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; }
.cal-header { text-align: center; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 2px; }
.cal-day { aspect-ratio: 1; min-width: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; font-size: 13px; cursor: default; }
.cal-day:not(.has-event)::after { content: ''; height: 6px; }
button.cal-day { border: none; margin: 0; padding: 0; font: inherit; color: inherit; width: 100%; background: transparent; cursor: pointer; }
.cal-num { width: 34px; height: 34px; max-width: 100%; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
@media (max-width: 380px) { .cal-num { width: 30px; height: 30px; } }
.cal-day.has-event .cal-num { font-weight: 700; }
.cal-day.has-event:hover .cal-num { background: color-mix(in srgb, var(--accent) 14%, transparent); }
.cal-day.is-today .cal-num { background: var(--accent); color: var(--accent-on); font-weight: 700; }
.cal-day.selected .cal-num { outline: 2px solid var(--accent); outline-offset: 1px; }
.cal-day.out-of-month { opacity: .35; }
.cal-dots { display: flex; justify-content: center; align-items: center; gap: 2px; height: 6px; }
.cal-dots i { width: 5px; height: 5px; border-radius: 50%; background: var(--c, var(--accent)); }
.cal-dots b { font-size: 12px; line-height: 6px; font-weight: 700; color: var(--text-secondary); }
.cal-empty { text-align: center; color: var(--text-muted); font-size: 12px; margin-top: 10px; }
.cal-detail-panel { display: none; margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border-color); font-size: 13px; max-height: 340px; overflow-y: auto; overscroll-behavior: contain; }
/* agenda panjang (banyak proyek sehari) scroll di dalam panel, bukan molorin halaman */
.cal-agenda-date { position: sticky; top: 0; background: var(--bg-card); z-index: 1; }
.cal-agenda-date { font-size: 12px; font-weight: 700; color: var(--text-secondary); margin-bottom: 6px; }
.cal-event-item { display: flex; align-items: center; gap: 8px; padding: 6px 8px; border-left: 3px solid var(--c, var(--accent)); border-radius: 4px; background: color-mix(in srgb, var(--c, var(--accent)) 8%, transparent); }
.cal-event-item + .cal-event-item { margin-top: 4px; }
.cal-jam { flex: 0 0 38px; font-size: 12px; font-weight: 600; line-height: 1.2; }
.cal-jam small { display: block; font-weight: 400; color: var(--text-muted); }
.cal-info { flex: 1; width: 0; line-height: 1.3; }
.cal-info strong, .cal-meta { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.cal-meta { color: var(--text-secondary); font-size: 12px; }
.cal-aksi { display: flex; gap: 4px; flex-shrink: 0; }
.cal-aksi .btn { min-height: 28px; padding: 0 8px; font-size: 11.5px; }
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
    var meta = function(e) {
        return [e.lokasi, e.catatan, e.extras != null ? e.extras + ' Extras · ' + e.absensi : null]
            .filter(function(v) { return v != null && v !== ''; }).map(esc).join(' &middot; ');
    };
    var html = events.map(function(e) {
        return '<div class="cal-event-item" style="--c: ' + escAttr(e.warna) + '">' +
               '<div class="cal-jam">' + (e.jam_mulai ? esc(e.jam_mulai) + (e.jam_selesai ? '<small>' + esc(e.jam_selesai) + '</small>' : '') : '&mdash;') + '</div>' +
               '<div class="cal-info"><strong>' + esc(e.nama || 'Jadwal') + '</strong>' +
               (meta(e) ? '<span class="cal-meta">' + meta(e) + '</span>' : '') + '</div>' +
               (e.url_proyek ? '<div class="cal-aksi"><a class="btn btn-sm" href="' + escAttr(e.url_proyek) + '" title="Buka proyek">Proyek</a>' +
                   '<a class="btn btn-sm" href="' + escAttr(e.url_absensi) + '" title="Lihat absensi">Absensi</a></div>' : '') +
               '</div>';
    }).join('');
    panel.innerHTML = '<div class="cal-agenda-date">' + esc(el.dataset.label) + '</div>' + html;
    panel.style.display = 'block';
    document.querySelectorAll('.cal-day.has-event').forEach(function(d) { d.classList.remove('selected'); });
    el.classList.add('selected');
}
</script>
@endonce

<div class="jadwal-cal {{ $compact ? 'jadwal-cal-compact' : '' }}">
    <div class="cal-nav">
        <span class="cal-nav-title">{{ $targetBulan->translatedFormat('F Y') }}</span>
        @if (!$compact)
            <span class="cal-nav-btn">
                <a href="{{ request()->fullUrlWithQuery(['bulan' => $prevMonth]) }}" aria-label="Bulan sebelumnya">&#8249;</a>
                <a href="{{ request()->fullUrlWithQuery(['bulan' => $nextMonth]) }}" aria-label="Bulan berikutnya">&#8250;</a>
            </span>
        @endif
    </div>

    <div class="cal-header">
        @foreach (['S', 'S', 'R', 'K', 'J', 'S', 'M'] as $h)
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
                $proyek = $dayEvents->unique('casting_project_id')->values();
                $eventsData = $dayEvents->map(fn($e) => [
                    'nama' => $e->nama_produksi ?? '',
                    'lokasi' => $e->lokasi,
                    'jam_mulai' => $e->jam_mulai ? substr($e->jam_mulai, 0, 5) : null,
                    'jam_selesai' => $e->jam_selesai ? substr($e->jam_selesai, 0, 5) : null,
                    'catatan' => $e->catatan,
                    'warna' => $warna($e),
                    ...($detail ? [
                        'extras' => $e->jumlah_extras,
                        'absensi' => $e->absensi,
                        'url_proyek' => $e->url_proyek,
                        'url_absensi' => $e->url_absensi,
                    ] : []),
                ])->values()->toArray();
            @endphp
            @if ($dayEvents->isNotEmpty())
                <button type="button" id="{{ $calId }}-{{ $dateStr }}"
                    class="cal-day has-event {{ $isToday ? 'is-today' : '' }} {{ $isOutOfMonth ? 'out-of-month' : '' }}"
                    data-events="{{ json_encode($eventsData) }}"
                    data-label="{{ $cur->translatedFormat('l, j F Y') }}"
                    onclick="calClick(this, '{{ $calId }}')"
                    onmouseenter="calClick(this, '{{ $calId }}')"
                    aria-label="{{ $cur->translatedFormat('d F Y') }}, ada jadwal"
                >
                    <span class="cal-num">{{ $cur->day }}</span>
                    <span class="cal-dots">
                        @foreach ($proyek->take($proyek->count() > 3 ? 2 : 3) as $e)
                            <i style="--c: {{ $warna($e) }}"></i>
                        @endforeach
                        @if ($proyek->count() > 3)
                            <b>+</b>
                        @endif
                    </span>
                </button>
            @else
                <div class="cal-day {{ $isToday ? 'is-today' : '' }} {{ $isOutOfMonth ? 'out-of-month' : '' }}">
                    <span class="cal-num">{{ $cur->day }}</span>
                </div>
            @endif
            @php $cur->addDay(); @endphp
        @endwhile
    </div>
    @if (!$hasEventsThisMonth)
        <div class="cal-empty">Tidak ada jadwal bulan ini.</div>
    @endif
    <div id="cal-detail-{{ $calId }}" class="cal-detail-panel"></div>
    @if ($defaultDate)
        <script>calClick(document.getElementById('{{ $calId }}-{{ $defaultDate }}'), '{{ $calId }}');</script>
    @endif
</div>
