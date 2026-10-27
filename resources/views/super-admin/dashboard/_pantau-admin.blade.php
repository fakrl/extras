@php $admin = $p['admin']; @endphp
<div class="pa-admin">
    <div class="pa-admin-nama">{{ $admin->name }}</div>
    @foreach ($p['tugas'] as $t)
        <div class="dash-row">
            <div>
                <strong>{{ $t['proyek'] }}</strong>
                <div class="dash-sub">{{ $t['tugas'] }} &bull; sejak {{ $t['sejak']?->diffForHumans() }}</div>
            </div>
            @if ($t['diingatkan_pada'])
                <span class="dash-sub">Sudah diingatkan {{ $t['diingatkan_pada']->diffForHumans() }}</span>
            @else
                <button type="button" class="btn btn-sm" onclick="document.getElementById('ingatkan-{{ $t['app_id'] }}').showModal()">Ingatkan</button>
            @endif
        </div>
        <dialog id="ingatkan-{{ $t['app_id'] }}" class="dash-dialog">
            <form method="POST" action="{{ route('super-admin.pantau-admin.ingatkan') }}" class="dash-dialog-isi">
                @csrf
                <input type="hidden" name="project_application_id" value="{{ $t['app_id'] }}">
                <div class="dash-dialog-judul">Ingatkan {{ $admin->name }}?</div>
                <div class="dash-sub dash-dialog-field">{{ $t['tugas'] }} &bull; {{ $t['proyek'] }}</div>
                <label for="ingatkan-catatan-{{ $t['app_id'] }}">Catatan <span class="dash-dialog-hint">(boleh dikosongkan)</span></label>
                <textarea name="catatan" id="ingatkan-catatan-{{ $t['app_id'] }}" rows="2" maxlength="500" class="dash-dialog-field"></textarea>
                <div class="dash-dialog-btn">
                    <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                    <button type="submit" class="btn btn-sm btn-brand">Kirim Pengingat</button>
                </div>
            </form>
        </dialog>
    @endforeach
</div>
