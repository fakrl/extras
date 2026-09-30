@if (\App\Http\Controllers\Auth\GoogleController::aktif())
<a href="{{ route('google.redirect', ['mode' => 'login']) }}" class="btn-google">
    @include('partials.google-ikon')
    Lanjut dengan Google
</a>
<div class="auth-atau"><span>atau</span></div>
@endif
