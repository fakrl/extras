@props(['label', 'name', 'opsi', 'nilai' => null, 'multi' => false, 'baris' => false])
<div class="fpanel-grup">
    @if ($label)<div class="fpanel-label">{{ $label }}</div>@endif
    <div @class(['fpanel-chips', 'is-baris' => $baris])>
        @foreach ($opsi as $v => $l)
            <label class="tag-chip"><input type="{{ $multi ? 'checkbox' : 'radio' }}" class="sr-only" name="{{ $name }}" value="{{ $v }}" @checked(in_array((string) $v, array_map('strval', (array) ($nilai ?? '')), true))>{{ $l }}</label>
        @endforeach
    </div>
    {{ $slot }}
</div>
