{{-- BB: meta share (WA/Google). Param: $ogTitle, $ogDesc, $ogImage (opsional), $noindex (default true) --}}
@if ($noindex ?? true)
    <meta name="robots" content="noindex, nofollow">
@endif
<meta name="description" content="{{ $ogDesc }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ config('brand.name') }}">
<meta property="og:title" content="{{ $ogTitle }}">
<meta property="og:description" content="{{ $ogDesc }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ $ogImage ?? asset('images/brand/icon-512.png') }}">
<meta name="twitter:card" content="summary_large_image">
