{{-- BD.4: dialog aksi per akun (di luar form lain, AY.1). Param: u --}}
@unless ($u->is_protected)
    @if ($u->trashed())
        <dialog id="restore-dialog-{{ $u->id }}" class="akun-dialog">
            <form method="POST" action="{{ route('super-admin.admins.restore', $u->id) }}">
                @csrf @method('PATCH')
                <div class="akun-dialog-judul">Restore {{ $u->name }}?</div>
                <p class="akun-dialog-teks">Akun aktif lagi dan bisa login.</p>
                <div class="akun-dialog-btn">
                    <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                    <button type="submit" class="btn btn-sm btn-brand">Ya, restore</button>
                </div>
            </form>
        </dialog>
    @else
        @if ($u->role === 'extras')
            <dialog id="edit-user-dialog-{{ $u->id }}" class="akun-dialog" style="max-width: 480px; width: 95%;">
                <form method="POST" action="{{ route('super-admin.admins.extras-update', $u) }}">
                    @csrf @method('PATCH')
                    <div class="akun-dialog-judul" style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                        <span>Kelola Extras · {{ $u->name }}</span>
                        <span class="badge badge-netral" style="font-size: 11px; font-weight: normal;">Extras</span>
                    </div>

                    <div style="background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 8px 12px; margin-bottom: 12px; font-size: var(--fs-xs); color: var(--text-muted); line-height: 1.5;">
                        <div><strong>Akun:</strong> {{ $u->name }} {{ $u->username ? '(@'.$u->username.')' : '' }}</div>
                        <div><strong>Email:</strong> {{ $u->email ?? 'tanpa email' }}</div>
                    </div>

                    <label style="font-size: var(--fs-sm); font-weight: 600;">Grade Extras</label>
                    <select name="grade" style="margin-top: 4px; margin-bottom: 12px;">
                        <option value="belum" @selected(! $u->extrasProfile?->grade_saat_ini)>Belum dinilai</option>
                        <option value="A" @selected($u->extrasProfile?->grade_saat_ini === 'A')>Grade A (Utama / Profesional)</option>
                        <option value="B" @selected($u->extrasProfile?->grade_saat_ini === 'B')>Grade B (Standar / Berpengalaman)</option>
                        <option value="C" @selected($u->extrasProfile?->grade_saat_ini === 'C')>Grade C (Pemula / Baru)</option>
                    </select>

                    <label style="font-size: var(--fs-sm); font-weight: 600;">Tag / Kategori Look</label>
                    <div style="margin-top: 4px;">
                        @include('partials.tag-input', ['name' => 'tag_nama', 'selected' => $u->extrasProfile?->categories ?? collect()])
                    </div>

                    <div class="akun-dialog-btn" style="margin-top: 16px;">
                        <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                        <button type="submit" class="btn btn-sm btn-brand">Simpan</button>
                    </div>
                </form>
            </dialog>
        @else
            <dialog id="edit-user-dialog-{{ $u->id }}" class="akun-dialog">
                <form method="POST" action="{{ route('super-admin.admins.update', $u) }}">
                    @csrf @method('PATCH')
                    <div class="akun-dialog-judul">Edit akun · {{ $u->name }}</div>
                    <label>Nama <span class="wajib" aria-hidden="true">*</span></label>
                    <input type="text" name="name" value="{{ $u->name }}" required>

                    <label>Username</label>
                    <input type="text" name="username" value="{{ $u->username }}" placeholder="username">

                    <label>Email @unless ($u->isClient())<span class="wajib" aria-hidden="true">*</span>@endunless</label>
                    <input type="email" name="email" value="{{ $u->email }}" @required(! $u->isClient())>
                    
                    @if ($u->isClient())
                        <input type="hidden" name="role" value="client">
                    @else
                        <label>Role <span class="wajib" aria-hidden="true">*</span></label>
                        <select name="role" required data-awal="{{ $u->role }}" onchange="var b=this.closest('form').querySelector('.konfirmasi-role');var beda=this.value!==this.dataset.awal;b.hidden=!beda;b.querySelector('input').required=beda;">
                            <option value="admin" @selected($u->role === 'admin')>Admin</option>
                            <option value="korlap" @selected($u->role === 'korlap')>Koordinator Lapangan</option>
                        </select>
                        <label class="konfirmasi-role" hidden style="display: block; margin-top: 8px; font-size: var(--fs-sm);">
                            <input type="checkbox" name="konfirmasi_role" value="1" style="width: auto;">
                            Saya paham: mengganti peran staf akan mengubah hak akses penugasan akun ini.
                        </label>

                        @if (in_array($u->role, ['admin', 'korlap']))
                            <label style="margin-top: 8px;">Honor per Event (Rp)</label>
                            <input type="number" name="honor_nominal" value="{{ $u->honor_nominal }}" min="0" placeholder="Rp 0">
                        @endif
                    @endif

                    <div class="akun-dialog-btn">
                        <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                        <button type="submit" class="btn btn-sm btn-brand">Simpan</button>
                    </div>
                </form>
            </dialog>
        @endif

        <dialog id="toggle-dialog-{{ $u->id }}" class="akun-dialog">
            <form method="POST" action="{{ route('super-admin.admins.toggle-status', $u) }}">
                @csrf @method('PATCH')
                <div class="akun-dialog-judul">{{ $u->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }} {{ $u->name }}?</div>
                <p class="akun-dialog-teks">{{ $u->status === 'aktif' ? 'Akun tidak bisa login sampai diaktifkan lagi.' : 'Akun bisa login lagi.' }}</p>
                <div class="akun-dialog-btn">
                    <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                    <button type="submit" class="btn btn-sm btn-brand">Lanjut</button>
                </div>
            </form>
        </dialog>

        <dialog id="hapus-dialog-{{ $u->id }}" class="akun-dialog">
            <form method="POST" action="{{ route('super-admin.admins.destroy', $u) }}">
                @csrf @method('DELETE')
                <div class="akun-dialog-judul">Arsipkan {{ $u->name }}?</div>
                <p class="akun-dialog-teks">Akun diarsipkan dan tidak bisa login. Histori tetap aman. Bisa dipulihkan lewat filter status "Dihapus" lalu Restore.</p>
                <div class="akun-dialog-btn">
                    <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger-outline">Arsipkan</button>
                </div>
            </form>
        </dialog>
    @endif
@endunless
