@if ($keputusan->isEmpty())
    <p class="kc-kosong">Belum ada keputusan Client.</p>
@else
    <ul class="kc-list">
        @foreach ($keputusan as $r)
            @php $app = $r->projectApplication; $lock = $r->keputusan === 'approve'; @endphp
            <li>
                <span><em>{{ $r->client?->name ?? 'Client' }}</em> <strong @class(['kc-lock' => $lock, 'kc-tolak' => ! $lock])>{{ $lock ? 'lock' : 'tolak' }}</strong> {{ $app?->extras?->user?->username ? '@'.$app->extras->user->username : 'Extras' }} — {{ $app?->castingProject?->nama_produksi ?? '-' }}@if ($lock && $r->grade_client) · Grade {{ $r->grade_client }}@endif</span>
                <time datetime="{{ $r->created_at->toIso8601String() }}" title="{{ $r->created_at->translatedFormat('d M Y H:i') }}">{{ $r->created_at->locale('id')->diffForHumans() }}</time>
            </li>
        @endforeach
    </ul>
@endif
