{{-- CE: isi dialog "Undang ke proyek…". Param: user, proyek, ada (project_id => status), bentrok (project_id => pendaftaran bentrok). Script inline di atribut: HTML ini dimuat lewat innerHTML. --}}
<div class="xmodal-head">
    <div style="min-width: 0;">
        <div class="xmodal-name">Undang ke proyek</div>
        <div class="xmodal-sub">{{ $user->username ? '@'.$user->username : $user->name }} · {{ $user->name }}</div>
    </div>
    <button type="button" class="xmodal-x" style="position: static; flex-shrink: 0; background: var(--bg-card-hover); color: var(--text-primary);" aria-label="Tutup" onclick="this.closest('dialog').close()"><i class="ti ti-x" aria-hidden="true"></i></button>
</div>

@if ($proyek->isEmpty())
    <div class="alert-info" style="margin: 14px 0 0;">Belum ada proyek yang bisa diundangi (proyek yang sudah selesai tidak ditampilkan).</div>
@else
    <form method="POST" action="{{ route('admin.undangan.store', $user) }}" style="margin-top: 14px;"
          onsubmit="var o = this.casting_project_id.selectedOptions[0]; if (o && o.dataset.bentrok) { if (!confirm('Tanggalnya bentrok dengan ' + o.dataset.bentrok + ' yang masih diproses. Tetap undang?')) return false; this.konfirmasi_bentrok.value = '1'; } return true;">
        @csrf
        <input type="hidden" name="konfirmasi_bentrok" value="0">
        <label for="undang-proyek">Proyek <span class="wajib" aria-hidden="true">*</span></label>
        <select name="casting_project_id" id="undang-proyek" required
                onchange="var f = this.form, p = this.value, k = f.casting_project_class_id, n = 0; [].forEach.call(k.options, function (o) { var s = !o.value || o.dataset.p === p; o.hidden = !s; o.disabled = !s || o.dataset.penuh === '1'; if (s && o.value) n++; }); k.value = ''; k.required = n > 0; f.querySelector('.undang-peran').hidden = !n;">
            <option value="">Pilih proyek</option>
            @foreach ($proyek as $p)
                @php
                    $lawan = $bentrok[$p->id];
                    $pasti = $lawan->first(fn ($b) => $b->isPasti());
                    $proses = $lawan->reject(fn ($b) => $b->isPasti())->map(fn ($b) => $b->castingProject->nama_produksi)->join(', ', ' dan ');
                @endphp
                <option value="{{ $p->id }}" @disabled($ada->has($p->id) || $pasti) @if ($proses) data-bentrok="{{ $proses }}" @endif>
                    {{ $p->namaKode() }}@if ($p->status === 'ditutup') · lowongan ditutup @endif
                    @if ($ada->has($p->id)) · sudah terdaftar ({{ \App\Models\ProjectApplication::LABELS[$ada[$p->id]] ?? $ada[$p->id] }})
                    @elseif ($pasti) · bentrok dengan {{ $pasti->castingProject->nama_produksi }} (sudah pasti)
                    @elseif ($proses) · bentrok dengan {{ $proses }} @endif
                </option>
            @endforeach
        </select>

        <div class="undang-peran" hidden>
            <label for="undang-peran">Peran <span class="wajib" aria-hidden="true">*</span></label>
            <select name="casting_project_class_id" id="undang-peran">
                <option value="">Pilih peran</option>
                @foreach ($proyek as $p)
                    @foreach ($p->classes as $c)
                        <option value="{{ $c->id }}" data-p="{{ $p->id }}" data-penuh="{{ $c->sisaKuota() ? 0 : 1 }}" hidden disabled>{{ $c->nama_kelas }} · {{ $c->sisaKuota() ? 'sisa '.$c->sisaKuota() : 'penuh' }}</option>
                    @endforeach
                @endforeach
            </select>
        </div>

        <p style="font-size: var(--fs-xs); color: var(--text-muted); margin: 0 0 14px;">Extras menerima undangan di aplikasinya. Untuk melobi lewat WhatsApp, pakai tombol Hubungi via WA.</p>
        <div style="display: flex; gap: 8px; justify-content: flex-end;">
            <button type="button" class="btn" onclick="this.closest('dialog').close()">Batal</button>
            <button type="submit" class="btn btn-brand"><i class="ti ti-user-plus" aria-hidden="true"></i> Undang</button>
        </div>
    </form>
@endif
