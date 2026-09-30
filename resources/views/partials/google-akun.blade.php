@if (\App\Http\Controllers\Auth\GoogleController::aktif())
@php $akun = auth()->user(); @endphp
<div class="card" style="padding:20px; margin-top:16px;">
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
        @include('partials.google-ikon')
        <span style="font-size:14px; font-weight:600;">Akun Google</span>
    </div>
    @if ($akun->google_id)
        <p style="font-size:13px; color:var(--text-secondary); margin:0 0 12px;">Terhubung. Kamu bisa masuk lewat tombol "Lanjut dengan Google".</p>
        @if ($akun->password)
            <form method="POST" action="{{ route('google.putus') }}" onsubmit="return confirm('Putuskan akun Google? Setelah ini masuk pakai username/email & kata sandi.')">
                @csrf
                <button type="submit" class="btn" style="min-height:40px;">Putuskan Google</button>
            </form>
        @else
            <p style="font-size:12px; color:var(--text-muted); margin:0;">Buat kata sandi dulu kalau mau memutus Google, supaya akun tidak terkunci.</p>
        @endif
    @else
        <p style="font-size:13px; color:var(--text-secondary); margin:0 0 12px;">Hubungkan supaya bisa masuk dengan satu klik lewat Google.</p>
        <a href="{{ route('google.redirect', ['mode' => 'hubungkan']) }}" class="btn" style="min-height:40px; display:inline-flex; align-items:center;">Hubungkan akun Google</a>
    @endif
</div>
@endif
