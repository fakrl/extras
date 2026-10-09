{{-- BA.4: kartu Extras. Param: profile, user?, aplikasi?, badge? [label, class], highlight?, check? [name, class, form], lihat [href (+ data-profil-modal, data-aksi-*)|onclick], aksi? [label, href|onclick|post (+method, confirm)], sub?, peringatan?, attrs?, favorit? (bintang Admin/SA), wa? + pesanWa? (tombol WA Admin/SA) --}}
@php
    $aplikasi ??= null;
    $favorit = ($favorit ?? false) && $profile && auth()->user()?->bisaSebagaiAdmin();
    $user ??= $profile?->user;
    $nama = $user?->username;
    $label = $nama ? '@'.$nama : '(belum isi username)';
    $badge ??= $aplikasi ? [$aplikasi->label(), $aplikasi->badgeClass()] : null;
    $persen = $aplikasi?->persenCocok();
    $dicari = $aplikasi?->castingProjectClass?->categories?->modelKeys() ?? [];
    $tags = ($profile?->categories ?? collect())->sortByDesc(fn ($t) => in_array($t->id, $dicari))->values();
    $peran = $aplikasi ? ($aplikasi->karakter ?: ($aplikasi->castingProjectClass->nama_kelas ?? 'Umum')) : null;
    $fisik = array_filter([
        $profile?->usia ? $profile->usia.' th' : null,
        $profile?->tinggi_badan ? $profile->tinggi_badan.' cm' : null,
        $profile?->gender ? ucfirst($profile->gender) : null,
    ]);
    $selesai = $profile?->proyek_selesai_count;
    $lihatTag = isset($lihat['href']) ? 'a' : 'button';
    $lihatAttr = isset($lihat['href']) ? $lihat : ['type' => 'button'] + $lihat;
    $bisaAdmin = auth()->user()?->bisaSebagaiAdmin();
    $tombolWa = ($wa ?? false) && $bisaAdmin && $user?->nomorWaInternasional();
@endphp
<article {{ (new \Illuminate\View\ComponentAttributeBag($attrs ?? []))->class(['xcard', 'is-highlight' => $highlight ?? false, 'has-ring' => $persen !== null]) }}>
    <div class="xcard-ph" style="--h: {{ crc32((string) $nama) % 360 }};">
        <{{ $lihatTag }} {{ new \Illuminate\View\ComponentAttributeBag($lihatAttr) }} class="xcard-ph-btn" aria-label="Lihat profil {{ $label }}">
            @if ($profile?->foto_profil_path)
                <img src="{{ route('extras.media.foto', $profile) }}" alt="" loading="lazy">
            @else
                <span class="xcard-inisial" aria-hidden="true">{{ $nama ? strtoupper(mb_substr($nama, 0, 2)) : '?' }}</span>
            @endif
        </{{ $lihatTag }}>
        @if ($badge)
            <span class="badge {{ $badge[1] }} xcard-st">{{ $badge[0] }}</span>
        @endif
        @if (! empty($check))
            <label class="xcard-ck">
                <input type="checkbox" name="{{ $check['name'] }}" value="{{ $aplikasi?->id }}" class="{{ $check['class'] }}" @isset($check['form']) form="{{ $check['form'] }}" @endisset>
                <span class="sr-only">Pilih {{ $label }}</span>
            </label>
        @endif
        @if ($favorit)
            <form method="POST" action="{{ route('admin.extras.favorit', $profile->user_id) }}" class="xcard-fav" id="fav-{{ $profile->id }}">
                @csrf @method('PATCH')
                <button type="submit" @class(['is-on' => $profile->apresiasi]) aria-pressed="{{ $profile->apresiasi ? 'true' : 'false' }}"
                        aria-label="{{ $profile->apresiasi ? 'Hapus '.$label.' dari Favorit' : 'Jadikan '.$label.' Favorit' }}"
                        title="{{ $profile->apresiasi ? '⭐ Favorit'.($profile->apresiasi_catatan ? ': '.$profile->apresiasi_catatan : '') : 'Jadikan Favorit' }}">@if ($profile->apresiasi)<span aria-hidden="true">⭐</span>@else<i class="ti ti-star" aria-hidden="true"></i>@endif</button>
            </form>
        @endif
        @if ($persen !== null)
            <div class="xcard-ring" style="--p: {{ $persen }}%;" role="img" aria-label="Cocok {{ $persen }}% dengan tag peran"><span>{{ $persen }}%</span></div>
        @endif
    </div>

    <div class="xcard-body">
        <div class="xcard-name" title="{{ $label }}">{{ $label }}</div>
        @if (! empty($sub))
            <div class="xcard-sub">{{ $sub }}</div>
        @endif
        @if ($peran)
            <div class="xcard-line"><i class="ti ti-masks-theater" aria-hidden="true"></i><span>{{ $peran }}</span></div>
        @endif
        @if ($fisik)
            <div class="xcard-line"><i class="ti ti-ruler-2" aria-hidden="true"></i><span>{{ implode(' · ', $fisik) }}</span></div>
        @endif
        @if ($tags->isNotEmpty())
            <div class="xcard-tags">
                @foreach ($tags->take(3) as $tag)
                    <span @class(['xtag', 'is-hit' => in_array($tag->id, $dicari)])>#{{ $tag->nama }}</span>
                @endforeach
                @if ($tags->count() > 3)
                    <span class="xtag" title="{{ $tags->slice(3)->map(fn ($t) => '#'.$t->nama)->implode(' ') }}">+{{ $tags->count() - 3 }}</span>
                @endif
            </div>
        @endif
        @if ($selesai !== null)
            <div class="xcard-line"><i class="ti ti-movie" aria-hidden="true"></i><span>{{ $selesai ? $selesai.' proyek selesai' : 'Belum pernah ikut proyek' }}</span></div>
        @endif
        @if (! empty($peringatan))
            <div class="xcard-line is-warn"><i class="ti ti-alert-triangle" aria-hidden="true"></i><span>{{ $peringatan }}</span></div>
        @endif

        <div class="xcard-btns">
            <{{ $lihatTag }} {{ new \Illuminate\View\ComponentAttributeBag($lihatAttr) }} class="btn btn-outline-brand" aria-label="Lihat profil {{ $label }}">Lihat Profil</{{ $lihatTag }}>
            @if (! empty($aksi['post']))
                <x-confirm-form :action="$aksi['post']" :method="$aksi['method'] ?? 'POST'" :message="$aksi['confirm'] ?? 'Yakin?'">
                    <button type="submit" class="btn btn-brand">{{ $aksi['label'] }}</button>
                </x-confirm-form>
            @elseif (! empty($aksi['href']))
                <a href="{{ $aksi['href'] }}" class="btn btn-brand">{{ $aksi['label'] }}</a>
            @elseif (! empty($aksi['onclick']))
                <button type="button" class="btn btn-brand" onclick="{{ $aksi['onclick'] }}">{{ $aksi['label'] }}</button>
            @endif
        </div>
        @if ($tombolWa)
            <div class="xcard-btns xcard-btns-2">
                @include('partials.tombol-wa', ['user' => $user, 'pesanWa' => $pesanWa ?? null])
            </div>
        @endif
    </div>
</article>
