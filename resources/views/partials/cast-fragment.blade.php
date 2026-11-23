<div id="cast-fragment" aria-live="polite">
    <div class="cast-chips" aria-label="Filter kategori talent">
        <details class="cast-filter">
            <summary><i class="ti ti-filter" aria-hidden="true"></i> {{ $castKategoriAktif ?: 'Kategori' }}</summary>
            <div class="cast-filter-menu">
                <a href="{{ route('home') }}#cast" class="cast-chip {{ $castKategoriAktif ? '' : 'is-active' }}"
                    @if (! $castKategoriAktif) aria-current="true" @endif
                    data-cast-chip data-kategori="">Semua</a>
                @foreach ($castChips as $chip)
                    <a href="{{ route('home', ['kategori' => $chip->nama]) }}#cast" class="cast-chip {{ $castKategoriAktif === $chip->nama ? 'is-active' : '' }}"
                        @if ($castKategoriAktif === $chip->nama) aria-current="true" @endif
                        data-cast-chip data-kategori="{{ $chip->nama }}">{{ $chip->nama }}</a>
                @endforeach
            </div>
        </details>
        <button type="button" class="cast-shuffle" data-cast-shuffle><i class="ti ti-refresh" aria-hidden="true"></i> Acak lagi</button>
    </div>

    @if ($castExtras->isEmpty())
        <p class="cast-empty">Belum ada talent untuk kategori ini. <a href="{{ route('home') }}#cast" data-cast-chip data-kategori="">Tampilkan semua</a></p>
    @else
        <div class="cast-grid">
            @foreach ($castExtras as $ex)
                <a href="{{ route('public.extras.profile', $ex->share_token) }}" class="cast-card">
                    <div class="cast-photo-wrap">
                        <img src="{{ route('public.extras.foto', $ex->share_token) }}" alt="Foto {{ '@'.$ex->user->username }}" class="cast-photo" loading="lazy">
                    </div>
                    <div class="cast-name">{{ '@'.$ex->user->username }}</div>
                    @if ($ex->categories->isNotEmpty())
                        <div class="cast-tags">
                            @foreach ($ex->categories->take(2) as $tag)
                                <span class="cast-tag-pill">{{ $tag->nama }}</span>
                            @endforeach
                        </div>
                    @endif
                </a>
            @endforeach
        </div>
    @endif
</div>
