@extends('layouts.app')

@section('title', 'Kelola Tag')

@push('styles')
<style>
    .tagk-row { display: grid; gap: 10px; padding: 14px 0; border-top: 1px solid var(--border-color); }
    .tagk-row:first-child { border-top: 0; }
    .tagk-nama { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; font-weight: 600; }
    .tagk-meta { font-size: var(--fs-xs); color: var(--text-muted); font-weight: 400; }
    .tagk-form { display: flex; gap: 8px; align-items: center; margin: 0; }
    .tagk-form select, .tagk-form input { flex: 1; min-width: 0; min-height: 40px; margin: 0; padding: 6px 10px; font-size: var(--fs-sm); }
    .tagk-aksi { display: grid; gap: 8px; }
    @media (min-width: 720px) {
        .tagk-row { grid-template-columns: minmax(180px, 1fr) 2fr; align-items: center; }
        .tagk-aksi { grid-template-columns: 1fr 1fr; }
    }
</style>
@endpush

@section('content')
<p style="font-size: var(--fs-sm); color: var(--text-secondary); margin: 0 0 var(--space-3);">
    Tag baru dari Extras masuk grup <strong>Lainnya</strong> dan belum tampil di profil publik sampai dipindah ke grup lain (selain Look).
    Gabung dua tag yang sama artinya supaya filter &amp; % cocok tetap akurat.
</p>

<form method="GET" action="{{ route('admin.tags.index') }}" class="xtoolbar" id="live-form" data-live>
    <input type="search" name="q" value="{{ $q }}" class="xtoolbar-cari" placeholder="Cari tag…" aria-label="Cari tag">
    <select name="grup" aria-label="Grup">
        <option value="">Semua grup</option>
        @foreach ([...array_keys(\App\Models\ExtrasCategory::GRUP), 'Lainnya'] as $g)
            <option value="{{ $g }}" @selected($grup === $g)>{{ $g }}</option>
        @endforeach
    </select>
    <x-per-halaman :pilihan="\App\Support\PerHalaman::TABEL" :nilai="$tags->perPage()" />
</form>

<datalist id="tag-semua">
    @foreach ($semua as $n)
        <option value="{{ $n }}">
    @endforeach
</datalist>

<div data-live-target>
    <div class="card" style="padding-top: 4px; padding-bottom: 4px;">
        @forelse ($tags as $tag)
            <div class="tagk-row">
                <div class="tagk-nama">
                    #{{ $tag->nama }}
                    @unless ($tag->grup)<span class="badge badge-pending">Lainnya</span>@endunless
                    <span class="tagk-meta">{{ $tag->extras_profiles_count }} Extras · {{ $tag->casting_project_classes_count }} peran</span>
                </div>
                <div class="tagk-aksi">
                    <form method="POST" action="{{ route('admin.tags.update', $tag) }}" class="tagk-form">
                        @csrf @method('PATCH')
                        <label for="grup-{{ $tag->id }}" class="sr-only">Grup #{{ $tag->nama }}</label>
                        <select name="grup" id="grup-{{ $tag->id }}" onchange="this.form.requestSubmit()">
                            <option value="">Lainnya</option>
                            @foreach (array_keys(\App\Models\ExtrasCategory::GRUP) as $g)
                                <option value="{{ $g }}" @selected($tag->grup === $g)>{{ $g }}</option>
                            @endforeach
                        </select>
                        <noscript><button type="submit" class="btn btn-sm">Simpan</button></noscript>
                    </form>
                    <x-confirm-form action="{{ route('admin.tags.gabung', $tag) }}" class="tagk-form" message="Gabung #{{ $tag->nama }} ke tag tujuan? Semua Extras & peran pindah ke tag tujuan, lalu #{{ $tag->nama }} dihapus.">
                        <label for="gabung-{{ $tag->id }}" class="sr-only">Gabung #{{ $tag->nama }} ke</label>
                        <input type="text" name="tujuan" id="gabung-{{ $tag->id }}" list="tag-semua" placeholder="Gabung ke tag…" required autocomplete="off">
                        <button type="submit" class="btn btn-sm">Gabung</button>
                    </x-confirm-form>
                </div>
            </div>
        @empty
            <div style="text-align: center; color: var(--text-muted); padding: 24px 0;">Tidak ada tag yang cocok.</div>
        @endforelse
    </div>
    <x-pagination-bar :paginator="$tags" :pilihan="\App\Support\PerHalaman::TABEL" />
</div>
@endsection
