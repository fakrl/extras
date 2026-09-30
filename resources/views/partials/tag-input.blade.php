{{-- BJ.1: tag bebas (chip + autocomplete + saran per grup), JS di layouts/app. Param: name (tanpa []), selected (nama/model), tagGroups? --}}
@php
    $selected = collect($selected ?? [])->map(fn ($t) => is_string($t) ? $t : $t->nama)->values();
    $saran = ($tagGroups ?? \App\Models\ExtrasCategory::perGrup())->toBase()->except('Lainnya');
    $kecil = $selected->map(fn ($n) => mb_strtolower($n))->all();
@endphp
<div class="tag-input" data-tag-input data-name="{{ $name }}[]" data-max="{{ \App\Models\ExtrasCategory::MAKS }}" data-cari="{{ route('tag.cari') }}">
    <div class="tag-input-box">
        @foreach ($selected as $nama)
            <span class="tag-input-chip">#{{ $nama }}<input type="hidden" name="{{ $name }}[]" value="{{ $nama }}"><button type="button" data-tag-hapus aria-label="Hapus tag {{ $nama }}"><i class="ti ti-x" aria-hidden="true"></i></button></span>
        @endforeach
        <input type="text" class="tag-input-field" list="tag-datalist" maxlength="40" autocomplete="off" enterkeyhint="done" placeholder="+ Tambah tag" aria-label="Tambah tag, tekan Enter atau koma">
    </div>
    <p class="field-hint">Ketik lalu Enter atau koma. <span data-tag-hitung>{{ $selected->count() }}</span>/{{ \App\Models\ExtrasCategory::MAKS }} tag.</p>
    @foreach ($saran as $grup => $tags)
        <div class="tag-grup">{{ $grup }}</div>
        <div class="tag-chips">
            @foreach ($tags as $tag)
                <button type="button" class="tag-chip" data-tag-saran="{{ $tag->nama }}" aria-pressed="{{ in_array(mb_strtolower($tag->nama), $kecil) ? 'true' : 'false' }}">#{{ $tag->nama }}</button>
            @endforeach
        </div>
    @endforeach
</div>
