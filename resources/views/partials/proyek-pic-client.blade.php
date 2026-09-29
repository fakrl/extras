<div class="form-row">
    <div>
        <label for="admin_id">Admin PIC</label>
        <input type="search" placeholder="Cari admin..." data-cari-select="admin_id" style="margin-bottom: 6px;">
        <select name="admin_id" id="admin_id" @required($adminWajib)>
            <option value="">{{ $adminKosong }}</option>
            @foreach ($admins as $a)
                <option value="{{ $a->id }}" @selected((string) old('admin_id', $adminId) === (string) $a->id)>{{ $a->name }}{{ $a->username ? ' (@'.$a->username.')' : '' }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="client_id">Akun Client</label>
        <input type="search" placeholder="Cari client..." data-cari-select="client_id" style="margin-bottom: 6px;">
        <select name="client_id" id="client_id" required>
            <option value="">- Pilih Client -</option>
            @foreach ($clients as $c)
                <option value="{{ $c->id }}" @selected((string) old('client_id', session('client_baru_id', $clientId)) === (string) $c->id)>{{ $c->name }}{{ $c->username ? ' (@'.$c->username.')' : '' }}</option>
            @endforeach
        </select>
        @if (auth()->user()->isSuperAdmin())
            <button type="button" class="btn btn-sm" style="margin-top: 6px;" onclick="document.getElementById('client-baru-dialog').showModal()">+ Client baru</button>
            <p style="font-size: var(--fs-xs); color: var(--text-muted); margin: 4px 0 0;">Isian form ini belum tersimpan akan hilang, buat Client dulu sebelum mengisi.</p>
        @else
            <p style="font-size: var(--fs-xs); color: var(--text-muted); margin: 4px 0 0;">Client belum ada? Minta Super Admin membuatkan akunnya.</p>
        @endif
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
