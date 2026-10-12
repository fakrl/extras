@php
    $mendadak = $app->batalMendadak();
    $id = 'dialog-batal-'.$app->id;
@endphp
<button type="button" class="btn btn-danger-outline" onclick="document.getElementById('{{ $id }}').showModal()">Batalkan keikutsertaan</button>
<dialog id="{{ $id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 380px; width: 90%;">
    <form method="POST" action="{{ route('extras.negotiations.batalkan', $app) }}" style="padding: 18px;">
        @csrf
        <div style="font-size: 14px; font-weight: 600; margin-bottom: 8px;">Batalkan keikutsertaan di {{ $app->castingProject->nama_produksi }}?</div>
        <label for="alasan-{{ $app->id }}" style="font-size: 13px;">Alasan <span class="wajib" aria-hidden="true">*</span></label>
        <textarea id="alasan-{{ $app->id }}" name="alasan" rows="3" required maxlength="500" style="width: 100%; margin-bottom: 8px;"></textarea>
        @if ($mendadak)
            <p style="font-size: 13px; margin: 0 0 8px; line-height: 1.5; font-weight: 600; color: var(--danger);">Syuting kurang dari 2 hari lagi, jadi ini dihitung batal mendadak.</p>
        @endif
        <p style="font-size: 12px; margin: 0 0 14px; line-height: 1.5; color: var(--text-muted);">Batal mendadak (kurang dari H-2 syuting) yang sudah kamu lakukan: {{ $jumlahBatalMendadak }} dari 3. Mencapai 3 kali, akunmu ditandai melanggar dan tidak bisa dipakai masuk.</p>
        <div style="display: flex; gap: 8px; justify-content: flex-end;">
            <button type="button" class="btn" onclick="this.closest('dialog').close()">Tutup</button>
            <button type="submit" class="btn btn-danger-outline">Ya, batalkan</button>
        </div>
    </form>
</dialog>
