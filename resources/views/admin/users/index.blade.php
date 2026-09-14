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
            <tr><th>Nama</th><th>Alias</th><th>Email</th><th>Status</th><th>Pembatalan Mendadak</th><th></th></tr>
        </thead>
        <tbody>
            @foreach ($extras as $ex)
                <tr data-status="{{ $ex->status }}"
                    data-nama="{{ strtolower($ex->name) }}"
                    data-alias="{{ strtolower($ex->extrasProfile->alias_tampil ?? '') }}"
                    data-email="{{ strtolower($ex->email) }}">
                    <td>{{ $ex->name }}</td>
                    <td>{{ $ex->extrasProfile->alias_tampil ?? '-' }}</td>
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
