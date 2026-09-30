{{-- BR.2: feed keputusan Client, refresh otomatis 30 detik (fetch partial, jeda saat tab tidak aktif). Param: keputusan?, kecil? --}}
@php $kecil ??= false; @endphp
<div class="card kc {{ $kecil ? 'is-kecil' : '' }}">
    <div class="card-title"><i class="ti ti-gavel"></i> Keputusan Client terbaru</div>
    <div data-feed-keputusan="{{ route('admin.akun.client.keputusan', $kecil ? ['kecil' => 1] : []) }}" aria-live="polite">
        @include('partials.keputusan-client-list', ['keputusan' => $keputusan ?? \App\Support\KeputusanClient::terbaru()])
    </div>
</div>
@once
@push('styles')
<style>
    .kc-list { list-style: none; margin: 0; padding: 0; }
    .kc-list li { display: flex; justify-content: space-between; gap: 10px; padding: 8px 0; border-top: 1px solid var(--border-color); font-size: var(--fs-sm); min-width: 0; }
    .kc-list li:first-child { border-top: 0; }
    .kc-list li > span { min-width: 0; overflow-wrap: anywhere; }
    .kc-list time { flex-shrink: 0; font-size: var(--fs-xs); color: var(--text-muted); white-space: nowrap; }
    .kc-lock { color: var(--accent-strong); }
    .kc-tolak { color: var(--danger); }
    .kc-kosong { margin: 0; font-size: var(--fs-sm); color: var(--text-muted); }
    .kc.is-kecil .kc-list { max-height: 260px; overflow-y: auto; }
    .kc.is-kecil .kc-list li { font-size: var(--fs-xs); padding: 6px 0; }
</style>
@endpush
@push('scripts')
<script>
(function () {
    function segarkan() {
        if (document.hidden) return;
        document.querySelectorAll('[data-feed-keputusan]').forEach(function (el) {
            fetch(el.dataset.feedKeputusan, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.ok ? r.text() : Promise.reject(); })
                .then(function (html) { el.innerHTML = html; })
                .catch(function () {});
        });
    }
    setInterval(segarkan, 30000);
    document.addEventListener('visibilitychange', segarkan);
}());
</script>
@endpush
@endonce
