@php $lampiran = $project->attachments()->with('pengunggah')->get(); @endphp
<div class="card lampiran">
    <div class="card-title">Lampiran Proyek <span class="badge badge-netral">{{ $lampiran->count() }}</span></div>

    @forelse ($lampiran as $f)
        <div class="lampiran-item">
            <div class="lampiran-info">
                <div class="lampiran-nama"><i class="ti ti-file" aria-hidden="true"></i><span>{{ $f->nama_asli }}</span></div>
                <div class="lampiran-meta">{{ $f->pengunggah?->name ?? '-' }} · {{ $f->created_at->translatedFormat('d M Y H:i') }} · {{ $f->ukuranLabel() }}</div>
                @if ($f->keterangan)
                    <div class="lampiran-meta">{{ $f->keterangan }}</div>
                @endif
            </div>
            <div class="lampiran-aksi">
                <a href="{{ route('project-attachments.download', $f) }}" class="btn btn-sm">Unduh</a>
                @if (auth()->user()->isSuperAdmin() || (int) $f->uploaded_by === auth()->id())
                    <x-confirm-form :action="route('project-attachments.destroy', $f)" method="DELETE" :message="'Hapus lampiran '.$f->nama_asli.'?'">
                        <button type="submit" class="btn btn-sm btn-danger-outline">Hapus</button>
                    </x-confirm-form>
                @endif
            </div>
        </div>
    @empty
        <div style="color: var(--text-muted); font-size: var(--fs-sm); padding: 8px 0;">Belum ada lampiran.</div>
    @endforelse

    <form method="POST" action="{{ route('project-attachments.store', $project) }}" enctype="multipart/form-data" class="lampiran-form">
        @csrf
        <label for="lampiran-files">Unggah file (bisa pilih beberapa sekaligus) <span class="wajib" aria-hidden="true">*</span></label>
        <input type="file" id="lampiran-files" name="files[]" multiple required accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
        <div class="lampiran-meta">PDF, Word, Excel, JPG, PNG · maks 10 MB per file</div>
        <label for="lampiran-ket">Keterangan</label>
        <input type="text" id="lampiran-ket" name="keterangan" maxlength="255" value="{{ old('keterangan') }}" placeholder="mis. Brief final, rundown">
        <button type="submit" class="btn btn-brand">Unggah Lampiran</button>
    </form>
</div>

<style>
    .lampiran-item { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; padding: 10px 0; border-bottom: 1px solid var(--border-color); }
    .lampiran-info { min-width: 0; flex: 1 1 220px; }
    .lampiran-nama { display: flex; gap: 6px; align-items: baseline; font-weight: 600; font-size: var(--fs-sm); overflow-wrap: anywhere; }
    .lampiran-meta { font-size: var(--fs-xs); color: var(--text-muted); }
    .lampiran-aksi { display: flex; gap: 6px; }
    .lampiran-form { margin-top: 14px; display: grid; gap: 6px; }
    .lampiran-form label { margin: 6px 0 0; }
    .lampiran-form .btn { justify-self: start; margin-top: 6px; }
</style>
