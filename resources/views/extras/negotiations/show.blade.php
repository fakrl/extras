@extends('layouts.app')

@section('title', 'Negosiasi Fee')

@section('content')
<p style="color: var(--text-secondary); margin: -8px 0 20px; font-size: 13.5px;">
    Proyek: {{ $application->castingProject->nama_produksi }}
</p>

<div class="card" style="margin-bottom: 16px;">
    <div style="font-size: 14px; font-weight: 500; margin-bottom: 12px;">Riwayat Tawar-Menawar</div>
    @foreach ($application->feeNegotiations as $nego)
        <div style="padding: 10px 0; border-top: 1px solid var(--border-color);">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap;">
                <span style="font-size: 12.5px; color: var(--text-muted);">Ronde {{ $nego->round }} · {{ $nego->diajukan_oleh === 'admin' ? 'Admin' : 'Kamu' }}</span>
                <x-status-badge :model="$nego" />
            </div>
            <div style="font-weight: 600; color: var(--accent-strong); margin-top: 4px;">Rp {{ number_format($nego->nominal, 0, ',', '.') }}</div>
            @if ($nego->catatan)
                <div style="color: var(--text-secondary); font-size: 12.5px; margin-top: 2px;">{{ $nego->catatan }}</div>
            @endif
        </div>
    @endforeach
</div>

@if ($application->status_partisipasi === 'deal')
    <div class="alert-success">Fee sudah disepakati di Rp {{ number_format($application->fee_final, 0, ',', '.') }}. Tunggu kabar selanjutnya dari Admin.</div>
    <button type="button" class="btn btn-danger-outline" style="margin-top: 8px;" onclick="document.getElementById('batalkan-dialog').showModal()">Batalkan Keikutsertaan</button>

    <dialog id="batalkan-dialog" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 360px; width: 90%;">
        <form method="POST" action="{{ route('extras.negotiations.batalkan', $application) }}" style="padding: 18px;">
            @csrf
            <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">Batalkan keikutsertaan di proyek ini?</div>
            <textarea name="alasan" rows="3" required placeholder="Alasan pembatalan" style="width: 100%; margin-bottom: 12px;"></textarea>
            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button type="button" class="btn" onclick="this.closest('dialog').close()">Batal</button>
                <button type="submit" class="btn btn-danger-outline">Batalkan</button>
            </div>
        </form>
    </dialog>
@elseif ($application->status_partisipasi === 'ditolak')
    <div class="alert-info">Negosiasi untuk pendaftaran ini sudah dihentikan.</div>
@elseif (! $application->masihBisaNego())
    <div class="alert-info">Negosiasi untuk pendaftaran ini sudah selesai ({{ $application->label() }}).@if ($application->fee_final) Fee final Rp {{ number_format($application->fee_final, 0, ',', '.') }}.@endif</div>
@elseif ($application->feeNegotiations->isNotEmpty() && $application->feeNegotiations->last()->diajukan_oleh === 'extras' && $application->feeNegotiations->last()->aksi === 'counter')
    <div class="alert-info">Menunggu balasan Admin sejak {{ $application->feeNegotiations->last()->created_at->translatedFormat('d M Y H:i') }}.</div>
@elseif ($application->feeNegotiations->isNotEmpty())
    <div class="card" style="padding: 16px;">
        <div style="font-weight: 600; margin-bottom: 10px;">Tanggapan Kamu:</div>
        <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-start;">
            <x-confirm-form action="{{ route('extras.negotiations.terima', $application) }}" message="Terima Rp {{ number_format($application->feeNegotiations->last()->nominal, 0, ',', '.') }}? Fee akan terkunci dan tidak bisa dinego ulang.">
                <button type="submit" class="btn btn-brand">Terima Rp {{ number_format($application->feeNegotiations->last()->nominal, 0, ',', '.') }}</button>
            </x-confirm-form>

            @if ($application->castingProject->nego_terbuka)
            <form method="POST" action="{{ route('extras.negotiations.counter', $application) }}" style="display: flex; gap: 8px; flex-wrap: wrap; flex: 1;"
                  onsubmit="var n=Number(this.nominal.value); return confirm('Ajukan tawaran balik Rp '+n.toLocaleString('id-ID')+'? Admin akan menerima atau menawar lagi.');">
                @csrf
                <input type="number" name="nominal" class="input-inline" min="1" step="1000" placeholder="Nominal tawaran (Rp) *" required style="min-width: 150px;" oninput="this.form.querySelector('[data-pratinjau]').textContent = this.value ? 'Rp ' + Number(this.value).toLocaleString('id-ID') : ''">
                <input type="text" name="catatan" class="input-inline" placeholder="Alasan (misal: butuh transport)" style="flex: 1; min-width: 200px;">
                <div data-pratinjau style="width: 100%; font-weight: 600; color: var(--accent-strong);" aria-live="polite"></div>
                <button class="btn">Ajukan Tawar Balik</button>
            </form>
            @else
            <p style="margin: 0; flex: 1; font-size: 13.5px; color: var(--text-secondary);">Penawaran ini final, tidak bisa ditawar. Terima atau tolak.</p>
            <x-confirm-form action="{{ route('extras.negotiations.tolak', $application) }}" message="Tolak penawaran Rp {{ number_format($application->feeNegotiations->last()->nominal, 0, ',', '.') }}? Pendaftaran ini tidak akan dilanjutkan.">
                <input type="text" name="alasan" class="input-inline" placeholder="Alasan penolakan" maxlength="500" style="margin-bottom: 8px;">
                <button type="submit" class="btn btn-danger-outline">Tolak Penawaran</button>
            </x-confirm-form>
            @endif
        </div>
    </div>
@else
    <p style="color: var(--text-muted);">Menunggu penawaran fee dari Admin.</p>
@endif
@endsection
