{{-- BB: meta share (WA/Google). Param: $ogTitle, $ogDesc, $ogImage (opsional), $noindex (default true) --}}
@if ($noindex ?? true)
    <meta name="robots" content="noindex, nofollow">
@endif
<meta name="description" content="{{ $ogDesc }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="JBTB Casting">
<meta property="og:title" content="{{ $ogTitle }}">
<meta property="og:description" content="{{ $ogDesc }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ $ogImage ?? asset('images/logo-jbtb.jpg') }}">
<meta name="twitter:card" content="summary_large_image">
