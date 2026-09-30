@extends('layouts.app')

@section('title', 'Kelola Akun Client & Extras')

@section('content')
<div style="display: flex; justify-content: flex-end; margin-bottom: 12px;">
    <a href="{{ route('admin.tags.index') }}" class="btn btn-sm"><i class="ti ti-tags"></i> Kelola Tag</a>
</div>
<div class="card" style="margin-bottom: 20px;">
    <div style="font-size: 14px; font-weight: 500; margin-bottom: 12px;">Client</div>

    <div style="display:flex; gap:8px; margin-bottom:10px; align-items:center; flex-wrap:wrap;">
        <label style="margin:0; font-size:12.5px; color:var(--text-secondary);">Status:</label>
        <select id="filter-client-status" style="width:auto; min-height:unset; margin-bottom:0; padding:4px 8px; font-size:12.5px;">
            <option value="">Semua</option>
            <option value="aktif">Aktif</option>
            <option value="nonaktif">Nonaktif</option>
        </select>
        <input id="filter-client-search" type="search" placeholder="Cari nama / email Client…"
               style="width:220px; min-height:unset; margin-bottom:0; padding:4px 10px; font-size:var(--fs-md);">
    </div>

    <div class="table-container">
    <table id="tabel-client">
        <thead>
            <tr><th>Nama</th><th>Email</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            @foreach ($clients as $klien)
                <tr data-status="{{ $klien->status }}"
                    data-nama="{{ strtolower($klien->name) }}"
                    data-email="{{ strtolower($klien->email) }}">
                    <td>{{ $klien->name }}</td>
                    <td>{{ $klien->email }}</td>
                    <td>
                        <span class="badge {{ $klien->status === 'aktif' ? 'badge-aktif' : 'badge-tolak' }}">
                            {{ $klien->status }}
                        </span>
                    </td>
                    <td>
                        <x-confirm-form action="{{ route('admin.users.toggle-status', $klien) }}" method="PATCH" message="{{ $klien->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }} akun {{ $klien->name }}?">
                            <button type="submit" class="btn btn-sm" aria-label="{{ $klien->status === 'aktif' ? 'Nonaktifkan akun' : 'Aktifkan akun' }}"><i class="ti ti-power"></i> {{ $klien->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                        </x-confirm-form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>
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
    <div style="font-size: 14px; font-weight: 500; margin-bottom: 12px;">Extras <span style="color: var(--text-muted); font-weight: 400;">· {{ $extras->count() }} akun</span></div>

    <div style="display:flex; gap:8px; margin-bottom:14px; align-items:center; flex-wrap:wrap;">
        <label for="filter-ex-status" style="margin:0; font-size:12.5px; color:var(--text-secondary);">Status:</label>
        <select id="filter-ex-status" style="width:auto; min-height:unset; margin-bottom:0; padding:4px 8px; font-size:12.5px;">
            <option value="">Semua</option>
            <option value="aktif">Aktif</option>
            <option value="nonaktif">Nonaktif</option>
        </select>
        <input id="filter-ex-search" type="search" placeholder="Cari nama / alias / email…" aria-label="Cari Extras"
               style="width:220px; min-height:unset; margin-bottom:0; padding:4px 10px; font-size:var(--fs-md);">
    </div>

    <div class="xgrid" id="grid-extras">
        @forelse ($extras as $ex)
            @php $cancelCount = $ex->extrasProfile->cancel_count ?? 0; @endphp
            @include('partials.extras-card', [
                'profile' => $ex->extrasProfile,
                'user' => $ex,
                'badge' => [ucfirst($ex->status), $ex->status === 'aktif' ? 'badge-aktif' : 'badge-tolak'],
                'sub' => $ex->name,
                'lihat' => $ex->extrasProfile ? ['href' => route('admin.extras.profil', $ex), 'data-profil-modal' => true, 'data-aksi-dialog' => 'kelola-'.$ex->id, 'data-aksi-label' => 'Kelola'] : ['onclick' => "document.getElementById('kelola-{$ex->id}').showModal()"],
                'aksi' => ['label' => 'Kelola', 'onclick' => "document.getElementById('kelola-{$ex->id}').showModal()"],
                'favorit' => true,
                'peringatan' => $cancelCount ? $cancelCount.'x batal mendadak' : null,
                'attrs' => [
                    'data-status' => $ex->status,
                    'data-nama' => strtolower($ex->name),
                    'data-alias' => strtolower($ex->username ?? ''),
                    'data-email' => strtolower($ex->email),
                ],
            ])
        @empty
            <div style="grid-column: 1 / -1; text-align: center; color: var(--text-muted); padding: 24px 0;">Belum ada akun Extras.</div>
        @endforelse
    </div>
</div>

@foreach ($extras as $ex)
    @php $cancelCount = $ex->extrasProfile->cancel_count ?? 0; @endphp
    <dialog class="xmodal" id="kelola-{{ $ex->id }}" aria-label="Kelola {{ $ex->username ?? $ex->name }}" onclick="if (event.target === this) this.close()">
        <div class="xmodal-body">
            <div class="xmodal-head">
                <div style="min-width: 0;">
                    <div class="xmodal-name">{{ $ex->username ? '@'.$ex->username : '(belum isi username)' }}</div>
                    <div class="xmodal-sub">{{ $ex->name }} · {{ $ex->email }}</div>
                </div>
                <button type="button" class="xmodal-x" style="position: static; flex-shrink: 0; background: var(--bg-card-hover); color: var(--text-primary);" aria-label="Tutup" onclick="this.closest('dialog').close()"><i class="ti ti-x"></i></button>
            </div>
            <div class="xmodal-badges">
                <span class="badge {{ $ex->status === 'aktif' ? 'badge-aktif' : 'badge-tolak' }}">{{ ucfirst($ex->status) }}</span>
                {{-- RF-08: hitungan dari cancellations (mendadak <H-2 oleh Extras), lihat ExtrasProfile::batalMendadak() --}}
                <span class="badge {{ $cancelCount >= 3 ? 'badge-tolak' : ($cancelCount > 0 ? 'badge-pending' : 'badge-aktif') }}">Batal mendadak {{ $cancelCount }}x</span>
            </div>

            @if ($ex->extrasProfile)
                <div class="xsec">Tag (koreksi Admin)</div>
                <form method="POST" action="{{ route('admin.users.kategori', $ex) }}">
                    @csrf @method('PATCH')
                    @include('partials.tag-input', ['name' => 'tag_nama', 'selected' => $ex->extrasProfile->categories])
                    <button type="submit" class="btn btn-brand" style="margin-top: 14px;">Simpan Tag</button>
                </form>
            @else
                <div class="alert-info" style="margin: 14px 0 0;">Profil Extras belum dibuat, tag belum bisa diisi.</div>
            @endif

            <div class="xsec">Status akun</div>
            <x-confirm-form action="{{ route('admin.users.toggle-status', $ex) }}" method="PATCH" message="{{ $ex->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }} akun {{ $ex->name }}?">
                <button type="submit" @class(['btn', 'btn-danger-outline' => $ex->status === 'aktif'])><i class="ti ti-power"></i> {{ $ex->status === 'aktif' ? 'Nonaktifkan akun' : 'Aktifkan akun' }}</button>
            </x-confirm-form>
        </div>
    </dialog>
@endforeach
@endsection

@push('scripts')
<script>
(function () {
    function filterRows(selector, statusVal, searchVal) {
        document.querySelectorAll(selector).forEach(function (tr) {
            var matchStatus = !statusVal || tr.dataset.status === statusVal;
            var matchSearch = !searchVal || [tr.dataset.nama, tr.dataset.alias, tr.dataset.email]
                .some(function (v) { return v && v.includes(searchVal); });
            tr.style.display = (matchStatus && matchSearch) ? '' : 'none';
        });
    }

    var clientStatus = document.getElementById('filter-client-status');
    var clientSearch = document.getElementById('filter-client-search');
    function applyClientFilter() {
        filterRows('#tabel-client tbody tr', clientStatus ? clientStatus.value : '', clientSearch ? clientSearch.value.toLowerCase().trim() : '');
    }
    clientStatus && clientStatus.addEventListener('change', applyClientFilter);
    clientSearch && clientSearch.addEventListener('input', applyClientFilter);

    var exStatus = document.getElementById('filter-ex-status');
    var exSearch = document.getElementById('filter-ex-search');
    function applyExtrasFilter() {
        filterRows('#grid-extras .xcard', exStatus ? exStatus.value : '', exSearch ? exSearch.value.toLowerCase().trim() : '');
    }
    exStatus && exStatus.addEventListener('change', applyExtrasFilter);
    exSearch && exSearch.addEventListener('input', applyExtrasFilter);
})();
</script>
@endpush
