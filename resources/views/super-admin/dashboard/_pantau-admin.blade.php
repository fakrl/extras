@php $admin = $p['admin']; @endphp
<div class="pa-admin" style="padding: 10px 0; border-top: 1px solid var(--border-color);">
    <div style="font-weight: 600; margin-bottom: 6px;">{{ $admin->name }}</div>
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
        <dialog id="ingatkan-{{ $t['app_id'] }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 380px; width: 90%;">
            <form method="POST" action="{{ route('super-admin.pantau-admin.ingatkan') }}" style="padding: 18px;">
                @csrf
                <input type="hidden" name="project_application_id" value="{{ $t['app_id'] }}">
                <div style="font-size: 14px; font-weight: 600; margin-bottom: 8px;">Ingatkan {{ $admin->name }}?</div>
                <div class="dash-sub" style="margin-bottom: 8px;">{{ $t['tugas'] }} &bull; {{ $t['proyek'] }}</div>
                <label for="ingatkan-catatan-{{ $t['app_id'] }}">Catatan <span style="color: var(--text-muted); font-weight: 400;">(boleh dikosongkan)</span></label>
                <textarea name="catatan" id="ingatkan-catatan-{{ $t['app_id'] }}" rows="2" maxlength="500" style="width: 100%; margin-bottom: 12px;"></textarea>
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                    <button type="submit" class="btn btn-sm btn-brand">Kirim Pengingat</button>
                </div>
            </form>
        </dialog>
    @endforeach
</div>
