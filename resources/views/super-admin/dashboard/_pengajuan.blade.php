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
            <div class="dash-sub" style="margin-top: 4px;"><strong>Catatan dari JBTB:</strong> {{ $req->alasan_tolak }}</div>
        @endif
        <details style="margin-top: 6px; font-size: 13px;">
            <summary style="cursor: pointer; color: var(--accent);">Lihat pengajuan lengkap</summary>
            <div style="margin-top: 6px;">Deadline: <strong>{{ $req->deadline?->translatedFormat('d M Y') ?? '-' }}</strong> &bull; Budget total: <strong>{{ $req->budget_total !== null ? $rpAjuan($req->budget_total) : '-' }}</strong></div>
            <div style="margin-top: 6px;"><strong>Peran ({{ $req->kuota }} orang):</strong>
                <ul style="margin: 4px 0 0 18px;">
                    @foreach ($req->classes as $kls)
                        <li>{{ $kls->nama_kelas }} &times; {{ $kls->kuota_kelas }} orang @if ($kls->kriteria) &mdash; {{ $kls->kriteria }} @endif</li>
                    @endforeach
                </ul>
            </div>
            <div style="margin-top: 6px;"><strong>Tanggal shooting:</strong>
                <ul style="margin: 4px 0 0 18px;">
                    @foreach ($req->shootingDates as $tgl)
                        <li>{{ $tgl->tanggal->translatedFormat('d M Y') }} @if ($tgl->lokasi) &mdash; {{ $tgl->lokasi }} @endif</li>
                    @endforeach
                </ul>
            </div>
            <div style="margin-top: 6px; white-space: pre-line;"><strong>Catatan Client:</strong> {{ $req->brief_catatan ?: '-' }}</div>
            @if ($req->attachments->isNotEmpty())
                <div style="margin-top: 6px;"><strong>Lampiran:</strong>
                    <ul style="margin: 4px 0 0 18px;">
                        @foreach ($req->attachments as $f)
                            <li><a href="{{ route('project-attachments.download', $f) }}">{{ $f->nama_asli }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </details>
    </div>
    <div style="display: flex; gap: 12px; flex-shrink: 0; flex-wrap: wrap;">
        <button type="button" class="btn btn-sm btn-brand" onclick="document.getElementById('setujui-req-{{ $req->id }}').showModal()">Setujui</button>
        <button type="button" class="btn btn-sm" onclick="document.getElementById('revisi-req-{{ $req->id }}').showModal()">Minta Revisi</button>
        <button type="button" class="btn btn-sm btn-danger-outline" onclick="document.getElementById('tolak-req-{{ $req->id }}').showModal()">Tolak</button>
    </div>
</div>
<dialog id="setujui-req-{{ $req->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 380px; width: 90%;">
    <form method="POST" action="{{ route('super-admin.projects.acc', $req) }}" style="padding: 18px;">
        @csrf @method('PATCH')
        <div style="font-size: 14px; font-weight: 600; margin-bottom: 8px;">Setujui pengajuan {{ $req->nama_produksi }}?</div>
        <label for="admin-req-{{ $req->id }}">Admin PIC <span class="wajib" aria-hidden="true">*</span></label>
        <select name="admin_id" id="admin-req-{{ $req->id }}" required style="margin-bottom: 10px;">
            <option value="">- Pilih Admin -</option>
            @foreach ($adminsAktif as $a)
                <option value="{{ $a->id }}">{{ $a->name }}</option>
            @endforeach
        </select>
        <label for="korlap-req-{{ $req->id }}">Korlap <span style="color: var(--text-muted); font-weight: 400;">(boleh dikosongkan)</span></label>
        <select name="korlap_id" id="korlap-req-{{ $req->id }}" style="margin-bottom: 12px;">
            <option value="">- Tidak ada -</option>
            @foreach ($korlapsAktif as $k)
                <option value="{{ $k->id }}">{{ $k->name }}</option>
            @endforeach
        </select>
        <div style="display: flex; gap: 8px; justify-content: flex-end;">
            <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
            <button type="submit" class="btn btn-sm btn-brand">Setujui Pengajuan</button>
        </div>
    </form>
</dialog>
<dialog id="revisi-req-{{ $req->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 380px; width: 90%;">
    <form method="POST" action="{{ route('super-admin.projects.minta-revisi', $req) }}" style="padding: 18px;">
        @csrf @method('PATCH')
        <div style="font-size: 14px; font-weight: 600; margin-bottom: 8px;">Minta revisi pengajuan {{ $req->nama_produksi }}?</div>
        <label for="revisi-catatan-{{ $req->id }}">Catatan untuk Client <span class="wajib" aria-hidden="true">*</span></label>
        <textarea name="alasan_tolak" id="revisi-catatan-{{ $req->id }}" rows="3" required maxlength="500" style="width: 100%; margin-bottom: 12px;"></textarea>
        <div style="display: flex; gap: 8px; justify-content: flex-end;">
            <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
            <button type="submit" class="btn btn-sm btn-brand">Kirim Minta Revisi</button>
        </div>
    </form>
</dialog>
<dialog id="tolak-req-{{ $req->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 380px; width: 90%;">
    <form method="POST" action="{{ route('super-admin.projects.reject', $req) }}" style="padding: 18px;">
        @csrf @method('PATCH')
        <div style="font-size: 14px; font-weight: 600; margin-bottom: 8px;">Tolak pengajuan {{ $req->nama_produksi }}?</div>
        <label for="alasan-req-{{ $req->id }}">Alasan penolakan (dikirim ke Client) <span class="wajib" aria-hidden="true">*</span></label>
        <textarea name="alasan_tolak" id="alasan-req-{{ $req->id }}" rows="3" required maxlength="500" style="width: 100%; margin-bottom: 12px;"></textarea>
        <div style="display: flex; gap: 8px; justify-content: flex-end;">
            <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
            <button type="submit" class="btn btn-sm btn-danger-outline">Tolak Pengajuan</button>
        </div>
    </form>
</dialog>
