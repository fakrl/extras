@php $idDialog = $idPrefix.$project->id; @endphp
<dialog id="{{ $idDialog }}" class="dash-dialog">
    <form method="POST" action="{{ route('super-admin.projects.tetapkan-pic', $project) }}" class="dash-dialog-isi">
        @csrf @method('PATCH')
        <div class="dash-dialog-judul">Tetapkan PIC untuk {{ $project->nama_produksi }}</div>
        <label for="{{ $idDialog }}-admin">Admin PIC <span class="wajib" aria-hidden="true">*</span></label>
        <select name="admin_id" id="{{ $idDialog }}-admin" required class="dash-dialog-field">
            <option value="">- Pilih Admin -</option>
            @foreach ($adminsAktif as $a)
                <option value="{{ $a->id }}">{{ $a->name }}</option>
            @endforeach
        </select>
        <label for="{{ $idDialog }}-korlap">Korlap <span class="dash-dialog-hint">(boleh dikosongkan)</span></label>
        <select name="korlap_id" id="{{ $idDialog }}-korlap" class="dash-dialog-field">
            <option value="">- Tidak ada -</option>
            @foreach ($korlapsAktif as $k)
                <option value="{{ $k->id }}">{{ $k->name }}</option>
            @endforeach
        </select>
        @include('partials.staf-baru-mini', ['uid' => $idDialog.'-stafbaru', 'selectAdmin' => $idDialog.'-admin', 'selectKorlap' => $idDialog.'-korlap'])
        <div class="dash-dialog-btn">
            <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
            <button type="submit" class="btn btn-sm btn-brand">Tetapkan</button>
        </div>
    </form>
</dialog>
