@props(['pilihan', 'nilai'])
<label class="per-halaman">Per halaman
    <select name="per" {{ $attributes }}>
        @foreach ($pilihan as $n)
            <option value="{{ $n }}" @selected($n === (int) $nilai)>{{ $n }}</option>
        @endforeach
    </select>
</label>
