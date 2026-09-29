@props(['pilihan', 'nilai'])
<label class="per-halaman">Tampilkan
    <select name="per" aria-label="Jumlah per halaman" {{ $attributes }}>
        @foreach ($pilihan as $n)
            <option value="{{ $n }}" @selected($n === (int) $nilai)>{{ $n }}</option>
        @endforeach
    </select>
</label>
