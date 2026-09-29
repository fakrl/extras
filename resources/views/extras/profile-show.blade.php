@extends('layouts.app')

@php $mode ??= 'pemilik'; @endphp
@section('title', ['admin' => 'Profil Extras — '.$profile->user->name, 'client' => 'Profil @'.$profile->user->username][$mode] ?? 'Profil Saya')

@section('content')
@if (session('status'))
    <div class="alert-success">{{ session('status') }}</div>
@endif
@include('partials.profil-extras-app')

@if ($mode === 'pemilik')
<dialog id="modal-share" style="border:1px solid var(--border-color); border-radius:16px; padding:0; max-width:360px; width:95%;">
    <div style="padding:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <span style="font-size:15px; font-weight:600;">Bagikan Profil Kamu</span>
            <button type="button" onclick="document.getElementById('modal-share').close()" aria-label="Tutup"
                style="background:none; border:none; cursor:pointer; font-size:20px; color:var(--text-muted); line-height:1;">×</button>
        </div>

        <div style="display:flex; gap:8px; margin-bottom:16px;">
            <input id="share-url-modal" readonly type="text" value="{{ route('public.extras.profile', $profile->share_token) }}"
                style="flex:1; font-size:12px; padding:8px 10px; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-card-hover); color:var(--text-primary); min-width:0;">
            <button type="button" id="btn-copy-link" title="Salin link" aria-label="Salin link"
                style="flex-shrink:0; align-self:stretch; padding:0 12px; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-card-hover); cursor:pointer; color:var(--text-primary); display:flex; align-items:center;">
                <i class="ti ti-copy" style="font-size:16px; line-height:1; display:block;"></i>
            </button>
        </div>
        <p id="share-copied-modal" style="display:none; color:var(--accent); font-size:12px; margin:-10px 0 12px;">Link disalin!</p>

        <a href="https://wa.me/?text={{ urlencode(route('public.extras.profile', $profile->share_token)) }}" target="_blank" rel="noopener"
            style="display:flex; align-items:center; justify-content:center; gap:8px; width:100%; padding:10px; border-radius:10px; background:#25d366; color:#fff; text-decoration:none; font-size:14px; font-weight:600; box-sizing:border-box;">
            <i class="ti ti-brand-whatsapp" style="font-size:18px;"></i> WhatsApp
        </a>
    </div>
</dialog>

@push('scripts')
<script>
(function () {
    var modal = document.getElementById('modal-share');
    var copied = document.getElementById('share-copied-modal');

    modal.addEventListener('click', function (e) { if (e.target === modal) modal.close(); });

    document.getElementById('btn-share').addEventListener('click', function () {
        copied.style.display = 'none';
        modal.showModal();
    });

    document.getElementById('btn-copy-link').addEventListener('click', function () {
        var url = document.getElementById('share-url-modal').value;
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function () {
                copied.style.display = 'block';
                setTimeout(function () { copied.style.display = 'none'; }, 2000);
            });
        } else {
            document.getElementById('share-url-modal').select();
            document.execCommand('copy');
            copied.style.display = 'block';
            setTimeout(function () { copied.style.display = 'none'; }, 2000);
        }
    });
})();
</script>
@endpush
@endif
@endsection
