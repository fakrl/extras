@php $ringkasan = $o['kelompok']->map(fn ($g) => $g['tugas'].' · '.$g['jumlah'].' Extras di '.$g['proyek'])->implode(', '); @endphp
<x-baris-tugas :judul="$o['staf']->name" :meta="\App\Models\User::LABELS[$o['peran']].' · '.$ringkasan" :umur="'sejak '.$o['sejak']?->diffForHumans()">
    @if ($o['diingatkan_pada'])
        <span class="dash-sub">Sudah diingatkan {{ $o['diingatkan_pada']->diffForHumans() }}</span>
    @else
        <button type="button" class="btn" onclick="document.getElementById('dash-ingatkan-{{ $loop->index }}').showModal()">Ingatkan</button>
    @endif
</x-baris-tugas>
<dialog id="dash-ingatkan-{{ $loop->index }}" class="dash-dialog">
    <form method="POST" action="{{ route('super-admin.dashboard.ingatkan-staf') }}" class="dash-dialog-isi">
        @csrf
        <input type="hidden" name="peran" value="{{ $o['peran'] }}">
        <input type="hidden" name="staf_id" value="{{ $o['staf']->id }}">
        <div class="dash-dialog-judul">Ingatkan {{ $o['staf']->name }}?</div>
        <div class="dash-sub dash-dialog-field">{{ $ringkasan }}</div>
        <label for="dash-ingatkan-catatan-{{ $loop->index }}">Catatan <span class="dash-dialog-hint">(boleh dikosongkan)</span></label>
        <textarea name="catatan" id="dash-ingatkan-catatan-{{ $loop->index }}" rows="2" maxlength="500" class="dash-dialog-field"></textarea>
        <div class="dash-dialog-btn">
            <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
            <button type="submit" class="btn btn-sm btn-brand">Kirim Pengingat</button>
        </div>
    </form>
</dialog>
