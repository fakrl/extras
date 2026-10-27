@php
    $umur = $item['sejak']?->diffForHumans();
@endphp

@if ($item['tipe'] === 'pengajuan')
    @php $req = $item['req']; @endphp
    <x-baris-tugas :judul="$req->nama_produksi" :meta="($req->client?->name ?? '-')" :umur="$umur">
        <button type="button" class="btn" onclick="document.getElementById('tinjau-{{ $req->id }}').showModal()">Tinjau</button>
    </x-baris-tugas>
    <dialog id="tinjau-{{ $req->id }}" class="dash-dialog dash-dialog-lg">
        <div class="dash-dialog-isi">
            <div class="dash-dialog-judul">{{ $req->nama_produksi }}</div>
            <div class="dash-sub dash-dialog-sub">{{ $req->namaClient() }} &bull; {{ $req->client?->name }}</div>

            <form method="POST" action="{{ route('super-admin.projects.acc', $req) }}" class="dash-dialog-bagian">
                @csrf @method('PATCH')
                <label for="dash-admin-{{ $req->id }}">Admin PIC <span class="wajib" aria-hidden="true">*</span></label>
                <select name="admin_id" id="dash-admin-{{ $req->id }}" required class="dash-dialog-field">
                    <option value="">- Pilih Admin -</option>
                    @foreach ($adminsAktif as $a)
                        <option value="{{ $a->id }}">{{ $a->name }}</option>
                    @endforeach
                </select>
                <label for="dash-korlap-{{ $req->id }}">Korlap <span class="dash-dialog-hint">(boleh dikosongkan)</span></label>
                <select name="korlap_id" id="dash-korlap-{{ $req->id }}" class="dash-dialog-field">
                    <option value="">- Tidak ada -</option>
                    @foreach ($korlapsAktif as $k)
                        <option value="{{ $k->id }}">{{ $k->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-brand">Setujui Pengajuan</button>
            </form>

            <form method="POST" action="{{ route('super-admin.projects.minta-revisi', $req) }}" class="dash-dialog-bagian">
                @csrf @method('PATCH')
                <label for="dash-revisi-{{ $req->id }}">Minta revisi &mdash; catatan untuk Client <span class="wajib" aria-hidden="true">*</span></label>
                <textarea name="alasan_tolak" id="dash-revisi-{{ $req->id }}" rows="2" required maxlength="500" class="dash-dialog-field"></textarea>
                <button type="submit" class="btn">Kirim Minta Revisi</button>
            </form>

            <form method="POST" action="{{ route('super-admin.projects.reject', $req) }}">
                @csrf @method('PATCH')
                <label for="dash-tolak-{{ $req->id }}">Tolak &mdash; alasan (dikirim ke Client) <span class="wajib" aria-hidden="true">*</span></label>
                <textarea name="alasan_tolak" id="dash-tolak-{{ $req->id }}" rows="2" required maxlength="500" class="dash-dialog-field"></textarea>
                <button type="submit" class="btn btn-danger-outline">Tolak Pengajuan</button>
            </form>

            <button type="button" class="btn dash-dialog-tutup" onclick="this.closest('dialog').close()">Tutup</button>
        </div>
    </dialog>
@elseif ($item['tipe'] === 'tanpa_pic')
    @php $project = $item['project']; @endphp
    <x-baris-tugas :judul="$project->nama_produksi" :meta="$project->client?->name ?? 'PIC/Client belum diisi'" :umur="$umur">
        @if ($project->admin_id)
            <a href="{{ route('admin.projects.edit', $project) }}" class="btn">Buka</a>
        @else
            <button type="button" class="btn" onclick="document.getElementById('dash-pic-{{ $project->id }}').showModal()">Tetapkan PIC</button>
        @endif
    </x-baris-tugas>
    @unless ($project->admin_id)
        @include('super-admin.dashboard._tetapkan-pic-dialog', ['project' => $project, 'idPrefix' => 'dash-pic-'])
    @endunless
@else
    @php $user = $item['user']; @endphp
    <x-baris-tugas :judul="$user->name" meta="Akun belum ganti password" :umur="$umur">
        <a href="{{ route('super-admin.admins.show', $user) }}" class="btn">Lihat akun</a>
    </x-baris-tugas>
@endif
