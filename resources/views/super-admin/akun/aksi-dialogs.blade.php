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
        <dialog id="edit-user-dialog-{{ $u->id }}" class="akun-dialog">
            <form method="POST" action="{{ route('super-admin.admins.update', $u) }}">
                @csrf @method('PATCH')
                <div class="akun-dialog-judul">Edit akun · {{ $u->name }}</div>
                <label>Nama <span class="wajib" aria-hidden="true">*</span></label>
                <input type="text" name="name" value="{{ $u->name }}" required>
                <label>Email @unless ($u->isClient())<span class="wajib" aria-hidden="true">*</span>@endunless</label>
                <input type="email" name="email" value="{{ $u->email }}" @required(! $u->isClient())>
                <label>Role <span class="wajib" aria-hidden="true">*</span></label>
                <select name="role" required>
                    @foreach (['admin', 'korlap', 'client', 'extras'] as $r)
                        <option value="{{ $r }}" @selected($u->role === $r)>{{ \App\Models\User::LABELS[$r] }}</option>
                    @endforeach
                    @if (auth()->user()->is_protected)
                        <option value="super_admin" @selected($u->role === 'super_admin')>Super Admin</option>
                    @endif
                </select>
                <div class="akun-dialog-btn">
                    <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                    <button type="submit" class="btn btn-sm btn-brand">Simpan</button>
                </div>
            </form>
        </dialog>

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
                <div class="akun-dialog-judul">Hapus {{ $u->name }}?</div>
                <p class="akun-dialog-teks">Akun diarsipkan, histori tetap aman. Bisa di-restore dari filter status "Dihapus".</p>
                <div class="akun-dialog-btn">
                    <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger-outline">Hapus</button>
                </div>
            </form>
        </dialog>
    @endif
@endunless
