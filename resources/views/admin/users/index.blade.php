@extends('layouts.app')

@section('title', 'Kelola Akun CD & Extras')

@section('content')
<div class="card" style="margin-bottom: 20px;">
    <div style="font-size: 14px; font-weight: 500; margin-bottom: 12px;">Casting Director</div>

    <div style="display:flex; gap:8px; margin-bottom:10px; align-items:center;">
        <label style="margin:0; font-size:12.5px; color:var(--text-secondary);">Status:</label>
        <select id="filter-cd-status" style="width:auto; min-height:unset; margin-bottom:0; padding:4px 8px; font-size:12.5px;">
            <option value="">Semua</option>
            <option value="aktif">Aktif</option>
            <option value="nonaktif">Nonaktif</option>
        </select>
    </div>

    <table id="tabel-cd">
        <thead>
            <tr><th>Nama</th><th>Email</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            @foreach ($castingDirectors as $cd)
                <tr data-status="{{ $cd->status }}">
                    <td>{{ $cd->name }}</td>
                    <td>{{ $cd->email }}</td>
                    <td>
                        <span class="badge {{ $cd->status === 'aktif' ? 'badge-aktif' : 'badge-tolak' }}">
                            {{ $cd->status }}
                        </span>
                    </td>
                    <td>
                        <form method="POST" action="{{ route('admin.users.toggle-status', $cd) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm" title="Ubah Status"><i class="ti ti-power"></i></button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if (($mangkrakCount ?? 0) > 0)
    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
            <div style="font-weight: 600; font-size: 13.5px;"><i class="ti ti-trash"></i> Pembersihan Akun Mangkrak (>30 Hari)</div>
            <div style="font-size: 12.5px; color: var(--text-muted); margin-top: 2px;">
                Ditemukan <strong>{{ $mangkrakCount }}</strong> akun extras yang terdaftar lebih dari 30 hari lalu dengan profil tidak lengkap dan 0 riwayat apply proyek.
            </div>
        </div>
        <form method="POST" action="{{ route('admin.users.prune') }}" onsubmit="return confirm('Yakin ingin menghapus {{ $mangkrakCount }} akun extras mangkrak (>30 hari tanpa kelengkapan profil & pendaftaran)?')">
            @csrf
            <button type="submit" class="btn btn-sm btn-danger-outline">Bersihkan {{ $mangkrakCount }} Akun Mangkrak</button>
        </form>
    </div>
@endif

<div class="card">
    <div style="font-size: 14px; font-weight: 500; margin-bottom: 12px;">Extras</div>

    <div style="display:flex; gap:8px; margin-bottom:10px; align-items:center; flex-wrap:wrap;">
        <label style="margin:0; font-size:12.5px; color:var(--text-secondary);">Status:</label>
        <select id="filter-ex-status" style="width:auto; min-height:unset; margin-bottom:0; padding:4px 8px; font-size:12.5px;">
            <option value="">Semua</option>
            <option value="aktif">Aktif</option>
            <option value="nonaktif">Nonaktif</option>
        </select>
        <input id="filter-ex-search" type="search" placeholder="Cari nama / alias / email…"
               style="width:220px; min-height:unset; margin-bottom:0; padding:4px 10px; font-size:12.5px;">
    </div>

    <table id="tabel-extras">
        <thead>
            <tr><th>Nama</th><th>Alias</th><th>Email</th><th>Status</th><th>Pembatalan Mendadak</th><th>Kategori</th><th></th></tr>
        </thead>
        <tbody>
            @foreach ($extras as $ex)
                <tr data-status="{{ $ex->status }}"
                    data-nama="{{ strtolower($ex->name) }}"
                    data-alias="{{ strtolower($ex->username ?? '') }}"
                    data-email="{{ strtolower($ex->email) }}">
                    <td>{{ $ex->name }}</td>
                    <td>{{ $ex->username ?? '-' }}</td>
                    <td>{{ $ex->email }}</td>
                    <td>
                        <span class="badge {{ $ex->status === 'aktif' ? 'badge-aktif' : 'badge-tolak' }}">
                            {{ $ex->status }}
                        </span>
                    </td>
                    <td>
                        {{-- RF-08: cancel_count cuma dari pembatalan mendadak (<H-2), lihat ProjectApplication::batalkan() --}}
                        @php $cancelCount = $ex->extrasProfile->cancel_count ?? 0; @endphp
                        <span class="badge {{ $cancelCount >= 3 ? 'badge-tolak' : ($cancelCount > 0 ? 'badge-pending' : 'badge-aktif') }}">
                            {{ $cancelCount }}x
                        </span>
                    </td>
                    <td>
                        @foreach ($ex->extrasProfile?->categories ?? [] as $kat)
                            <span class="badge badge-pending" style="font-size:11px; margin-bottom:2px;">{{ $kat->nama }}</span>
                        @endforeach
                        <details style="display:block; margin-top:4px;">
                            <summary style="font-size:11px; cursor:pointer; color:var(--accent); list-style:none;">Edit Kategori</summary>
                            <form method="POST" action="{{ route('admin.users.kategori', $ex) }}" style="margin-top:6px; padding:6px; background:var(--bg-card); border:1px solid var(--border-color); border-radius:4px;">
                                @csrf @method('PATCH')
                                @foreach ($allCategories as $kat)
                                    <label style="display:block; font-size:11px; margin-bottom:2px;">
                                        <input type="checkbox" name="kategori_ids[]" value="{{ $kat->id }}"
                                            {{ $ex->extrasProfile?->categories->contains('id', $kat->id) ? 'checked' : '' }}>
                                        {{ $kat->nama }}
                                    </label>
                                @endforeach
                                <button type="submit" class="btn btn-sm" style="margin-top:6px; padding:2px 8px;">Simpan</button>
                            </form>
                        </details>
                    </td>
                    <td>
                        <form method="POST" action="{{ route('admin.users.toggle-status', $ex) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm" title="Ubah Status"><i class="ti ti-power"></i></button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
<script>
(function () {
    function filterRows(tableId, statusVal, searchVal) {
        document.querySelectorAll('#' + tableId + ' tbody tr').forEach(function (tr) {
            var matchStatus = !statusVal || tr.dataset.status === statusVal;
            var matchSearch = !searchVal || [tr.dataset.nama, tr.dataset.alias, tr.dataset.email]
                .some(function (v) { return v && v.includes(searchVal); });
            tr.style.display = (matchStatus && matchSearch) ? '' : 'none';
        });
    }

    var cdStatus = document.getElementById('filter-cd-status');
    cdStatus && cdStatus.addEventListener('change', function () {
        filterRows('tabel-cd', this.value, '');
    });

    var exStatus = document.getElementById('filter-ex-status');
    var exSearch = document.getElementById('filter-ex-search');
    function applyExtrasFilter() {
        filterRows('tabel-extras', exStatus ? exStatus.value : '', exSearch ? exSearch.value.toLowerCase().trim() : '');
    }
    exStatus && exStatus.addEventListener('change', applyExtrasFilter);
    exSearch && exSearch.addEventListener('input', applyExtrasFilter);
})();
</script>
@endpush
