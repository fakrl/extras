@php $uid = $uid ?? 'stafbaru-'.\Illuminate\Support\Str::random(6); @endphp
<div class="staf-baru-mini">
    <button type="button" class="btn-tautan" data-staf-toggle="{{ $uid }}-admin">+ Admin baru</button>
    <button type="button" class="btn-tautan" data-staf-toggle="{{ $uid }}-korlap">+ Korlap baru</button>

    @foreach (['admin' => ['Admin', $selectAdmin], 'korlap' => ['Korlap', $selectKorlap]] as $role => [$label, $selectId])
    <div id="{{ $uid }}-{{ $role }}" class="staf-mini-form dash-dialog-field" data-role="{{ $role }}" data-target="{{ $selectId }}" hidden>
        <label>Nama {{ $label }} baru <span class="wajib" aria-hidden="true">*</span></label>
        <input type="text" data-field="nama" maxlength="255" class="dash-dialog-field">
        <label>Email <span class="wajib" aria-hidden="true">*</span></label>
        <input type="email" data-field="email" class="dash-dialog-field">
        <label>Nomor WA <span class="dash-dialog-hint">(boleh dikosongkan)</span></label>
        <input type="text" data-field="nomor_wa" class="dash-dialog-field">
        <div class="staf-mini-error" data-error style="color: var(--danger, #dc2626); font-size: var(--fs-xs);"></div>
        <div class="dash-dialog-btn">
            <button type="button" class="btn btn-sm" data-staf-cancel>Batal</button>
            <button type="button" class="btn btn-sm btn-brand" data-staf-submit>Buat &amp; pilih</button>
        </div>
    </div>
    @endforeach
</div>
