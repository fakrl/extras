@extends('layouts.app')

@section('title', 'Tagihan Saya')

@section('content')
<div style="font-size: 16px; font-weight: 600; margin-bottom: 4px;">Tagihan</div>
<p style="color: var(--text-secondary); margin: 0 0 20px; font-size: 13.5px;">Daftar proyek dan invoice terkait akun Anda.</p>

@forelse ($projects as $project)
    <div class="card" style="margin-bottom: 14px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 8px;">
            <div>
                <div style="font-size: 14.5px; font-weight: 600;">{{ $project->nama_produksi }}</div>
                <div style="font-size: 12.5px; color: var(--text-muted); display: flex; align-items: center; gap: 6px;">{{ $project->client_ph }} · Status: <x-status-badge :model="$project" /></div>
            </div>
            <a href="{{ route('invoices.show', $project) }}" class="btn btn-sm btn-brand">Lihat Invoice</a>
        </div>
    </div>
@empty
    <div class="card" style="text-align: center; color: var(--text-muted); padding: 30px 0;">
        Belum ada proyek yang terkait dengan akun Anda.
    </div>
@endforelse
@endsection
