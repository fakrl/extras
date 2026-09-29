@php($tagSaya ??= [])
@once
<style>
    .peran-list { display: flex; flex-direction: column; }
    .peran-item { padding: 10px 0; border-bottom: 1px solid var(--border-color); }
    .peran-item:first-child { padding-top: 0; }
    .peran-item:last-child { border-bottom: none; padding-bottom: 0; }
    .peran-head { display: flex; justify-content: space-between; align-items: baseline; gap: 8px; flex-wrap: wrap; }
    .peran-nama { font-weight: 600; font-size: 14px; }
    .peran-sisa { font-size: var(--fs-xs); font-weight: 600; color: var(--accent-strong); white-space: nowrap; }
    .peran-penuh { font-size: var(--fs-xs); font-weight: 600; padding: 2px 8px; border-radius: var(--radius-sm); color: var(--danger); background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.35); white-space: nowrap; }
    .peran-slot { margin-top: 4px; font-size: var(--fs-xs); color: var(--text-muted); }
    .peran-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
    .peran-tags .xtag .ti { font-size: 12px; }
    .peran-kriteria { margin-top: 6px; font-size: 13px; color: var(--text-secondary); line-height: 1.5; }
</style>
@endonce
<div class="peran-list">
@foreach ($classes as $class)
    <div class="peran-item">
        <div class="peran-head">
            <span class="peran-nama">{{ $class->nama_kelas }}</span>
            @if ($class->sisaKuota() === 0)
                <span class="peran-penuh">Penuh</span>
            @else
                <span class="peran-sisa">Sisa {{ $class->sisaKuota() }} dari {{ $class->kuota_kelas }}</span>
            @endif
        </div>
        @if ($class->sisaKuota() === 0)
            <div class="peran-slot">{{ $class->kuota_kelas }} dari {{ $class->kuota_kelas }} terisi · Slot bisa terbuka lagi</div>
        @endif
        @if ($class->categories->isNotEmpty())
            <div class="peran-tags" aria-label="Tag dicari">
                @foreach ($class->categories as $tag)
                    @if (in_array($tag->id, $tagSaya))
                        <span class="xtag is-hit" title="Kamu punya tag ini"><i class="ti ti-check" aria-hidden="true"></i> #{{ $tag->nama }}<span class="sr-only"> (kamu punya)</span></span>
                    @else
                        <span class="xtag">#{{ $tag->nama }}</span>
                    @endif
                @endforeach
            </div>
        @endif
        @if ($class->kriteria)
            <div class="peran-kriteria">{{ $class->kriteria }}</div>
        @endif
    </div>
@endforeach
</div>
