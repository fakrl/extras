{{-- CE: Terima / Tolak undangan proyek (Extras). Param: app, alasan? (kolom alasan menolak), konfirmasi? (Terima sudah dikonfirmasi soal bentrok) --}}
@php $namaProyek = $app->castingProject->nama_produksi; @endphp
<div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: flex-start;">
    <x-confirm-form :action="route('extras.undangan.terima', $app)" :message="'Terima undangan proyek '.$namaProyek.'?'">
        @if ($konfirmasi ?? false)
            <input type="hidden" name="konfirmasi_bentrok" value="1">
        @endif
        <button type="submit" class="btn btn-brand"><i class="ti ti-check" aria-hidden="true"></i> Terima</button>
    </x-confirm-form>
    <x-confirm-form :action="route('extras.undangan.tolak', $app)" :message="'Tolak undangan proyek '.$namaProyek.'?'" style="display: flex; flex-wrap: wrap; gap: 8px;">
        @if ($alasan ?? false)
            <input type="text" name="alasan" maxlength="255" placeholder="Alasan, jika ada" aria-label="Alasan menolak undangan, jika ada" style="width: auto; flex: 1 1 200px; margin: 0;">
        @endif
        <button type="submit" class="btn btn-danger-outline">Tolak</button>
    </x-confirm-form>
</div>
