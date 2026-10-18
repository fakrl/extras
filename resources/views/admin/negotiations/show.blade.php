@extends('layouts.app')

@section('title', 'Negosiasi Fee')

@section('content')
@php $bolehKelola = auth()->user()->can('kelola', $application->castingProject); @endphp
<p style="margin: -8px 0 8px;"><a href="{{ route('admin.projects.applicants', $application->castingProject) }}" style="color: var(--accent); font-size: 13px;">&larr; Kembali ke pendaftar</a></p>
<p style="color: var(--text-secondary); margin: 0 0 20px; font-size: 13.5px;">
    <strong>{{ '@'.$application->extras->user->username }}</strong> · {{ $application->castingProjectClass?->nama_kelas ?? 'Peran belum dipilih' }} · {{ $application->extras->user->name }}<br>
    Proyek: {{ $application->castingProject->nama_produksi }} ·
    Rate card awal: Rp {{ number_format($application->extras->rate_card ?? 0, 0, ',', '.') }} ·
    @if ($application->castingProjectClass?->budget_client)
        Budget client per orang: Rp {{ number_format($application->castingProjectClass->budget_client, 0, ',', '.') }} ·
    @endif
    Status: <x-status-badge :model="$application" />
    @unless ($application->castingProject->nego_terbuka)
        <span class="badge badge-netral">Fee tetap</span>
    @endunless
</p>

<div style="margin-bottom: 80px;">
    <div style="display: flex; flex-direction: column; gap: 10px; padding: 4px 0;">
        @forelse ($application->feeNegotiations as $nego)
            @php $isAdmin = $nego->diajukan_oleh === 'admin'; @endphp
            <div style="display: flex; justify-content: {{ $isAdmin ? 'flex-end' : 'flex-start' }};">
                <div style="max-width: 70%; background: {{ $isAdmin ? 'color-mix(in srgb, var(--accent-strong) 12%, var(--bg-card))' : 'var(--bg-secondary)' }}; border-radius: 12px; padding: 10px 14px; border: 1px solid var(--border-color);">
                    <div style="font-size: 15px; font-weight: 600; color: var(--accent-strong);">Rp {{ number_format($nego->nominal, 0, ',', '.') }}</div>
                    <div style="margin: 4px 0;">
                        <x-status-badge :model="$nego" />
                    </div>
                    @if ($nego->catatan)
                        <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">{{ $nego->catatan }}</div>
                    @endif
                    <div style="font-size: var(--fs-xs); color: var(--text-muted); margin-top: 6px; text-align: {{ $isAdmin ? 'right' : 'left' }};">
                        {{ ucfirst($nego->diajukan_oleh) }} · {{ $nego->created_at->translatedFormat('d M Y H:i') }}
                    </div>
                </div>
            </div>
        @empty
            <p style="color: var(--text-muted); text-align: center; padding: 20px 0;">Belum ada penawaran.</p>
        @endforelse
    </div>
</div>

<div style="position: sticky; bottom: 0; background: var(--bg-card); padding: 12px; border-top: 1px solid var(--border-color); z-index: 10;">
    @if ($application->status_partisipasi === 'deal')
        <div class="alert-success" style="margin-bottom: 8px;">Fee sudah Deal di Rp {{ number_format($application->fee_final, 0, ',', '.') }}.</div>
        @if ($bolehKelola)
        <x-confirm-form action="{{ route('admin.negotiations.ajukan-ke-client', $application) }}" message="Ajukan {{ $application->extras->user->name }} ke Client dengan fee Rp {{ number_format($application->fee_final, 0, ',', '.') }}? Setelah diajukan, Client yang memutuskan.">
            <button type="submit" class="btn btn-brand">Ajukan ke Client</button>
        </x-confirm-form>
        @endif
    @elseif ($application->status_partisipasi === 'ditolak')
        <div class="alert-info">Negosiasi untuk pendaftar ini sudah dihentikan.</div>
    @elseif (! $application->masihBisaNego())
        <div class="alert-info">Fee final Rp {{ number_format($application->fee_final ?? 0, 0, ',', '.') }}. Negosiasi sudah selesai ({{ $application->label() }}).</div>
    @elseif ($application->feeNegotiations->isEmpty())
        @if ($bolehKelola)
        <form method="POST" action="{{ route('admin.negotiations.ajukan', $application) }}" style="display: flex; gap: 8px; flex-wrap: wrap;">
            @csrf
            <input type="number" name="nominal" class="input-inline" min="1" placeholder="Nominal penawaran awal (Rp) *" required value="{{ $application->extras->rate_card }}" style="flex: 1 1 200px;" oninput="this.form.querySelector('[data-pratinjau]').textContent = this.value ? 'Rp ' + Number(this.value).toLocaleString('id-ID') : ''">
            <input type="text" name="catatan" class="input-inline" placeholder="Catatan/alasan" style="flex: 2 1 200px;">
            <div data-pratinjau style="width: 100%; font-weight: 600; color: var(--accent-strong);" aria-live="polite"></div>
            <button class="btn btn-brand">Ajukan Fee Awal</button>
        </form>
        @unless ($application->castingProject->nego_terbuka)
            <div style="font-size: var(--fs-xs); color: var(--text-muted); margin-top: 6px;">Penawaran ini final, Extras hanya bisa menerima atau menolak.</div>
        @endunless
        @endif
    @else
        @php $terakhir = $application->feeNegotiations->last(); $nominalTerakhir = $terakhir->nominal; $menungguExtras = $terakhir->diajukan_oleh === 'admin' && in_array($terakhir->aksi, ['tawar', 'counter'], true); @endphp
        @if ($menungguExtras)
            <div class="alert-info">Menunggu balasan Extras sejak {{ $terakhir->created_at->translatedFormat('d M Y H:i') }}.</div>
        @elseif ($bolehKelola)
        <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
            <x-confirm-form action="{{ route('admin.negotiations.terima', $application) }}" message="Terima Rp {{ number_format($nominalTerakhir, 0, ',', '.') }} untuk {{ $application->extras->user->name }}? Fee akan terkunci dan tidak bisa dinego ulang.">
                <input type="hidden" name="nominal" value="{{ $nominalTerakhir }}">
                <button type="submit" class="btn btn-brand">Terima Rp {{ number_format($nominalTerakhir, 0, ',', '.') }} untuk {{ $application->extras->user->name }}</button>
            </x-confirm-form>
            @if ($application->castingProject->nego_terbuka)
            <form method="POST" action="{{ route('admin.negotiations.counter', $application) }}" style="display: flex; gap: 8px; flex-wrap: wrap;">
                @csrf
                <input type="number" name="nominal" class="input-inline" min="1" placeholder="Nominal tawar balik (Rp) *" required style="flex: 1 1 160px;" oninput="this.form.querySelector('[data-pratinjau]').textContent = this.value ? 'Rp ' + Number(this.value).toLocaleString('id-ID') : ''">
                <input type="text" name="catatan" class="input-inline" placeholder="Catatan/alasan" style="flex: 2 1 180px;">
                <div data-pratinjau style="width: 100%; font-weight: 600; color: var(--accent-strong);" aria-live="polite"></div>
                <button class="btn">Tawar Balik</button>
            </form>
            @endif
        </div>
        @endif

        @if ($bolehKelola)
        <div style="margin-top: 20px; padding-top: 12px; border-top: 1px solid var(--border-color);">
            <button type="button" class="btn btn-danger-outline" onclick="document.getElementById('hentikan-nego-dialog').showModal()">Hentikan Negosiasi</button>
        </div>

        <dialog id="hentikan-nego-dialog" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 380px; width: 90%;">
            <form method="POST" action="{{ route('admin.negotiations.tolak', $application) }}" style="padding: 18px;">
                @csrf
                <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">Hentikan negosiasi fee untuk {{ $application->extras->user->name }}?</div>
                <p style="font-size: var(--fs-sm); color: var(--text-secondary); margin: 0 0 10px;">Pendaftar ini tidak bisa lanjut ke proses berikutnya. Jelaskan alasannya.</p>
                <textarea name="alasan" rows="3" required placeholder="Alasan menghentikan negosiasi" style="width: 100%; margin-bottom: 12px;"></textarea>
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <button type="button" class="btn" onclick="this.closest('dialog').close()">Batal</button>
                    <button type="submit" class="btn btn-danger-outline">Hentikan Negosiasi</button>
                </div>
            </form>
        </dialog>
        @endif
    @endif
</div>
@endsection
