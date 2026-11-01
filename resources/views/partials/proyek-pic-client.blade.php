<div class="form-row" style="margin-bottom: 16px;">
    <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <label for="admin_id" style="margin: 0; font-weight: 600;">Admin PIC @if ($adminWajib)<span class="wajib" aria-hidden="true">*</span>@endif</label>
            @if (auth()->user()->isSuperAdmin())
                <button type="button" class="btn btn-sm btn-tonal" style="padding: 2px 8px; font-size: 11px; height: auto;" onclick="document.getElementById('admin-baru-dialog').showModal()">
                    <i class="ti ti-plus"></i> Admin baru
                </button>
            @endif
        </div>
        <div style="position: relative;">
            <input type="search" placeholder="Cari nama admin / @username..." data-cari-select="admin_id" style="margin-bottom: 6px; font-size: 12.5px; padding: 6px 10px;">
            <select name="admin_id" id="admin_id" @required($adminWajib)>
                <option value="">{{ $adminKosong }}</option>
                @foreach ($admins as $a)
                    <option value="{{ $a->id }}" @selected((string) old('admin_id', session('admin_baru_id', $adminId)) === (string) $a->id)>{{ $a->name }}{{ $a->username ? ' (@'.$a->username.')' : '' }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <label for="client_id" style="margin: 0; font-weight: 600;">Akun Client <span class="wajib" aria-hidden="true">*</span></label>
            @if (auth()->user()->isSuperAdmin())
                <button type="button" class="btn btn-sm btn-tonal" style="padding: 2px 8px; font-size: 11px; height: auto;" onclick="document.getElementById('client-baru-dialog').showModal()">
                    <i class="ti ti-plus"></i> Client baru
                </button>
            @endif
        </div>
        <div style="position: relative;">
            <input type="search" placeholder="Cari nama client / @username..." data-cari-select="client_id" style="margin-bottom: 6px; font-size: 12.5px; padding: 6px 10px;">
            <select name="client_id" id="client_id" required>
                <option value="">- Pilih Client -</option>
                @foreach ($clients as $c)
                    <option value="{{ $c->id }}" @selected((string) old('client_id', session('client_baru_id', $clientId)) === (string) $c->id)>{{ $c->name }}{{ $c->username ? ' (@'.$c->username.')' : '' }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>
@once
@push('scripts')
<script>
    document.querySelectorAll('[data-cari-select]').forEach(function (input) {
        var select = document.getElementById(input.dataset.cariSelect);
        input.addEventListener('input', function () {
            var q = input.value.toLowerCase().trim();
            var pertama = null;
            Array.from(select.options).forEach(function (o) {
                var cocok = !o.value || !q || o.text.toLowerCase().includes(q);
                o.hidden = !cocok;
                if (cocok && o.value && !pertama) pertama = o;
            });
            if (q && pertama) select.value = pertama.value;
        });
    });
</script>
@endpush
@endonce
