@extends('layouts.app')

@section('title', 'Notifikasi')

@section('content')
<div style="max-width:720px;">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
        <h2 style="margin:0;font-size:18px;font-weight:600;">Notifikasi</h2>
        @if (auth()->user()->unreadNotifications()->exists())
            <form method="POST" action="{{ route('notifications.read-all') }}" style="margin:0;">
                @csrf
                <button type="submit" class="btn btn-sm">Tandai semua dibaca</button>
            </form>
        @endif
    </div>

    <div class="card" style="padding:0;overflow:hidden;">
        @forelse ($notifs as $notif)
            <a href="{{ route('notifications.baca', $notif->id) }}" style="display:block;color:inherit;text-decoration:none;padding:12px 16px;border-bottom:1px solid var(--border-color);{{ $notif->read_at ? '' : 'background:var(--bg-nav-active);' }}">
                <div style="font-size:14px;font-weight:{{ $notif->read_at ? '400' : '600' }};margin-bottom:2px;">{{ $notif->data['judul'] ?? '' }}</div>
                <div style="font-size:13px;color:var(--text-secondary);line-height:1.4;">{{ $notif->data['pesan'] ?? '' }}</div>
                <div style="font-size:var(--fs-xs);color:var(--text-muted);margin-top:3px;">{{ $notif->created_at->diffForHumans() }}</div>
            </a>
        @empty
            <div style="padding:24px 16px;font-size:13px;color:var(--text-muted);text-align:center;">Belum ada notifikasi.</div>
        @endforelse
    </div>

    <div style="margin-top:16px;">{{ $notifs->links() }}</div>
</div>
@endsection
