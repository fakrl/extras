@php
    $rpAjuan = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
    $mendesak = $req->created_at?->lte(now()->subDays(2));
@endphp
<div class="dash-row">
    <div>
        <span class="badge badge-pending">{{ $label }}</span>
        @if ($mendesak)<span class="badge badge-urgent">&gt; 2 hari</span>@endif
        <strong>{{ $req->nama_produksi }}</strong>
        <div class="dash-sub">{{ $req->namaClient() }} &bull; {{ $req->client?->name }}</div>
        @if ($req->alasan_tolak)
            <div class="dash-sub pu-catatan"><strong>Catatan dari JBTB:</strong> {{ $req->alasan_tolak }}</div>
        @endif
        <details class="pu-detail">
            <summary>Lihat pengajuan lengkap</summary>
            <div class="pu-detail-blok">Deadline: <strong>{{ $req->deadline?->translatedFormat('d M Y') ?? '-' }}</strong> &bull; Budget total: <strong>{{ $req->budget_total !== null ? $rpAjuan($req->budget_total) : '-' }}</strong></div>
            <div class="pu-detail-blok"><strong>Peran ({{ $req->kuota }} orang):</strong>
                <ul class="pu-detail-list">
                    @foreach ($req->classes as $kls)
                        <li>{{ $kls->nama_kelas }} &times; {{ $kls->kuota_kelas }} orang @if ($kls->kriteria) &mdash; {{ $kls->kriteria }} @endif</li>
                    @endforeach
                </ul>
            </div>
            <div class="pu-detail-blok"><strong>Tanggal shooting:</strong>
                <ul class="pu-detail-list">
                    @foreach ($req->shootingDates as $tgl)
                        <li>{{ $tgl->tanggal->translatedFormat('d M Y') }} @if ($tgl->lokasi) &mdash; {{ $tgl->lokasi }} @endif</li>
                    @endforeach
                </ul>
            </div>
            <div class="pu-detail-blok pu-pre"><strong>Catatan Client:</strong> {{ $req->brief_catatan ?: '-' }}</div>
            @if ($req->attachments->isNotEmpty())
                <div class="pu-detail-blok"><strong>Lampiran:</strong>
                    <ul class="pu-detail-list">
                        @foreach ($req->attachments as $f)
                            <li><a href="{{ route('project-attachments.download', $f) }}">{{ $f->nama_asli }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </details>
    </div>
    <div class="pu-aksi">
        <button type="button" class="btn btn-sm btn-brand" onclick="document.getElementById('setujui-req-{{ $req->id }}').showModal()">Setujui</button>
        <button type="button" class="btn btn-sm" onclick="document.getElementById('revisi-req-{{ $req->id }}').showModal()">Minta Revisi</button>
        <button type="button" class="btn btn-sm btn-danger-outline" onclick="document.getElementById('tolak-req-{{ $req->id }}').showModal()">Tolak</button>
    </div>
</div>
<dialog id="setujui-req-{{ $req->id }}" class="dash-dialog">
    <form method="POST" action="{{ route('super-admin.projects.acc', $req) }}" class="dash-dialog-isi">
        @csrf @method('PATCH')
        <div class="dash-dialog-judul">Setujui pengajuan {{ $req->nama_produksi }}?</div>
        <label for="admin-req-{{ $req->id }}">Admin PIC <span class="wajib" aria-hidden="true">*</span></label>
        <select name="admin_id" id="admin-req-{{ $req->id }}" required class="dash-dialog-field">
            <option value="">- Pilih Admin -</option>
            @foreach ($adminsAktif as $a)
                <option value="{{ $a->id }}">{{ $a->name }}</option>
            @endforeach
        </select>
        <label for="korlap-req-{{ $req->id }}">Korlap <span class="dash-dialog-hint">(boleh dikosongkan)</span></label>
        <select name="korlap_id" id="korlap-req-{{ $req->id }}" class="dash-dialog-field">
            <option value="">- Tidak ada -</option>
            @foreach ($korlapsAktif as $k)
                <option value="{{ $k->id }}">{{ $k->name }}</option>
            @endforeach
        </select>
        @include('partials.staf-baru-mini', ['uid' => 'stafbaru-req-'.$req->id, 'selectAdmin' => 'admin-req-'.$req->id, 'selectKorlap' => 'korlap-req-'.$req->id])
        <div class="dash-dialog-btn">
            <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
            <button type="submit" class="btn btn-sm btn-brand">Setujui Pengajuan</button>
        </div>
    </form>
</dialog>
<dialog id="revisi-req-{{ $req->id }}" class="dash-dialog">
    <form method="POST" action="{{ route('super-admin.projects.minta-revisi', $req) }}" class="dash-dialog-isi">
        @csrf @method('PATCH')
        <div class="dash-dialog-judul">Minta revisi pengajuan {{ $req->nama_produksi }}?</div>
        <label for="revisi-catatan-{{ $req->id }}">Catatan untuk Client <span class="wajib" aria-hidden="true">*</span></label>
        <textarea name="alasan_tolak" id="revisi-catatan-{{ $req->id }}" rows="3" required maxlength="500" class="dash-dialog-field"></textarea>
        <div class="dash-dialog-btn">
            <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
            <button type="submit" class="btn btn-sm btn-brand">Kirim Minta Revisi</button>
        </div>
    </form>
</dialog>
<dialog id="tolak-req-{{ $req->id }}" class="dash-dialog">
    <form method="POST" action="{{ route('super-admin.projects.reject', $req) }}" class="dash-dialog-isi">
        @csrf @method('PATCH')
        <div class="dash-dialog-judul">Tolak pengajuan {{ $req->nama_produksi }}?</div>
        <label for="alasan-req-{{ $req->id }}">Alasan penolakan (dikirim ke Client) <span class="wajib" aria-hidden="true">*</span></label>
        <textarea name="alasan_tolak" id="alasan-req-{{ $req->id }}" rows="3" required maxlength="500" class="dash-dialog-field"></textarea>
        <div class="dash-dialog-btn">
            <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
            <button type="submit" class="btn btn-sm btn-danger-outline">Tolak Pengajuan</button>
        </div>
    </form>
</dialog>
