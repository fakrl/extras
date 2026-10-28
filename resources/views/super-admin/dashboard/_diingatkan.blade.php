@php $staf = $t['peran'] === 'korlap' ? $t['korlap'] : $t['admin']; @endphp
<x-baris-tugas :judul="$t['proyek']" :meta="$staf->name.' ('.\App\Models\User::LABELS[$t['peran']].') &middot; '.$t['tugas'].' &middot; '.$t['jumlah'].' Extras'" :umur="'sejak '.$t['sejak']?->diffForHumans()">
    @if ($t['diingatkan_pada'])
        <span class="dash-sub">Sudah diingatkan {{ $t['diingatkan_pada']->diffForHumans() }}</span>
    @else
        <button type="button" class="btn" onclick="document.getElementById('dash-ingatkan-{{ $loop->index }}').showModal()">Ingatkan</button>
    @endif
</x-baris-tugas>
<dialog id="dash-ingatkan-{{ $loop->index }}" class="dash-dialog">
    <form method="POST" action="{{ $t['peran'] === 'korlap' ? route('super-admin.monitoring.korlap.ingatkan') : route('super-admin.pantau-admin.ingatkan') }}" class="dash-dialog-isi">
        @csrf
        @if ($t['peran'] === 'korlap')
            <input type="hidden" name="korlap_id" value="{{ $staf->id }}">
            <input type="hidden" name="proyek_id" value="{{ $t['proyek_id'] }}">
            <input type="hidden" name="jenis" value="{{ $t['jenis'] }}">
        @else
            @foreach ($t['app_ids'] as $id)
                <input type="hidden" name="project_application_ids[]" value="{{ $id }}">
            @endforeach
        @endif
        <div class="dash-dialog-judul">Ingatkan {{ $staf->name }}?</div>
        <div class="dash-sub dash-dialog-field">{{ $t['tugas'] }} &bull; {{ $t['jumlah'] }} Extras &bull; {{ $t['proyek'] }}</div>
        <label for="dash-ingatkan-catatan-{{ $loop->index }}">Catatan <span class="dash-dialog-hint">(boleh dikosongkan)</span></label>
        <textarea name="catatan" id="dash-ingatkan-catatan-{{ $loop->index }}" rows="2" maxlength="500" class="dash-dialog-field"></textarea>
        <div class="dash-dialog-btn">
            <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
            <button type="submit" class="btn btn-sm btn-brand">Kirim Pengingat</button>
        </div>
    </form>
</dialog>
