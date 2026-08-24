@extends('layouts.app')

@section('title', 'Buka Lowongan Casting')

@section('content')
<div class="card">
    <div style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">Buka Lowongan Casting Baru</div>

    @if ($errors->any())
        <div class="alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.projects.store') }}">
        @csrf

        <div class="form-row">
            <div>
                <label>Nama Produksi</label>
                <input type="text" name="nama_produksi" value="{{ old('nama_produksi') }}" required>
            </div>
            <div>
                <label>Client / Production House</label>
                <input type="text" name="client_ph" value="{{ old('client_ph') }}" required>
            </div>
        </div>

        <div class="form-row">
            <div>
                <label>Deadline Pendaftaran</label>
                <input type="date" name="deadline" value="{{ old('deadline') }}" required>
            </div>
            <div>
                <label>Kuota Total</label>
                <input type="number" name="kuota" value="{{ old('kuota') }}" min="1" required>
            </div>
            <div style="display: flex; align-items: center; padding-top: 22px;">
                <div class="form-check">
                    <input type="checkbox" name="is_urgent" value="1" id="is_urgent" @checked(old('is_urgent'))>
                    <label for="is_urgent" style="margin-bottom:0;">Butuh Dadakan / Urgent</label>
                </div>
            </div>
        </div>

        <hr>
        <div style="font-size: 14px; font-weight: 500; margin-bottom: 8px;">
            Tanggal Shooting <span style="color: var(--text-muted); font-weight: 400; font-size: 12.5px;">(bisa lebih dari satu, tidak harus berurutan)</span>
        </div>
        <div id="tanggal-wrap">
            <div style="display: flex; gap: 8px; margin-bottom: 8px; align-items: center;">
                <input type="date" name="tanggal_shooting[]" class="input-inline" required>
                <button type="button" class="btn-icon-danger btn-remove-row" title="Hapus" style="display:none">&times;</button>
            </div>
        </div>
        <button type="button" id="btn-add-tanggal" class="btn btn-sm" style="margin-bottom: 20px;">+ Tambah Tanggal</button>

        <hr>
        <div style="font-size: 14px; font-weight: 500; margin-bottom: 8px;">
            Kelas / Kriteria <span style="color: var(--text-muted); font-weight: 400; font-size: 12.5px;">(minimal satu kelas)</span>
        </div>
        <div id="kelas-wrap">
            <div class="kelas-row form-row" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 12px; margin-bottom: 10px; align-items: flex-end;">
                <div>
                    <label>Nama Kelas</label>
                    <input type="text" name="kelas[0][nama_kelas]" placeholder="misal: Ibu-ibu 29-50th" required>
                </div>
                <div>
                    <label>Budget Client (Rp)</label>
                    <input type="number" name="kelas[0][budget_client]" min="0" required>
                </div>
                <div>
                    <label>Kuota Kelas</label>
                    <input type="number" name="kelas[0][kuota_kelas]" min="1" required>
                </div>
                <div style="flex: 0;">
                    <button type="button" class="btn-icon-danger btn-remove-kelas" style="display:none">&times;</button>
                </div>
            </div>
        </div>
        <button type="button" id="btn-add-kelas" class="btn btn-sm" style="margin-bottom: 24px;">+ Tambah Kelas</button>

        <button type="submit" class="btn btn-brand" style="width: 100%;">Simpan Proyek Casting</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var tanggalWrap = document.getElementById('tanggal-wrap');
        document.getElementById('btn-add-tanggal').addEventListener('click', function () {
            var row = document.createElement('div');
            row.style.cssText = 'display:flex; gap:8px; margin-bottom:8px; align-items:center;';
            row.innerHTML = '<input type="date" name="tanggal_shooting[]" class="input-inline" required>' +
                '<button type="button" class="btn-icon-danger btn-remove-row">&times;</button>';
            tanggalWrap.appendChild(row);
            updateRemoveButtons(tanggalWrap, '.btn-remove-row');
        });
        tanggalWrap.addEventListener('click', function (e) {
            if (e.target.classList.contains('btn-remove-row')) {
                e.target.parentElement.remove();
                updateRemoveButtons(tanggalWrap, '.btn-remove-row');
            }
        });

        var kelasWrap = document.getElementById('kelas-wrap');
        var kelasIndex = 1;
        document.getElementById('btn-add-kelas').addEventListener('click', function () {
            var row = document.createElement('div');
            row.className = 'kelas-row form-row';
            row.style.cssText = 'border:1px solid var(--border-color); border-radius:10px; padding:12px; margin-bottom:10px; align-items:flex-end;';
            row.innerHTML =
                '<div><label>Nama Kelas</label>' +
                '<input type="text" name="kelas[' + kelasIndex + '][nama_kelas]" required></div>' +
                '<div><label>Budget Client (Rp)</label>' +
                '<input type="number" name="kelas[' + kelasIndex + '][budget_client]" min="0" required></div>' +
                '<div><label>Kuota Kelas</label>' +
                '<input type="number" name="kelas[' + kelasIndex + '][kuota_kelas]" min="1" required></div>' +
                '<div style="flex:0;"><button type="button" class="btn-icon-danger btn-remove-kelas">&times;</button></div>';
            kelasWrap.appendChild(row);
            kelasIndex++;
            updateRemoveButtons(kelasWrap, '.btn-remove-kelas');
        });
        kelasWrap.addEventListener('click', function (e) {
            if (e.target.classList.contains('btn-remove-kelas')) {
                e.target.closest('.kelas-row').remove();
                updateRemoveButtons(kelasWrap, '.btn-remove-kelas');
            }
        });

        function updateRemoveButtons(wrap, selector) {
            var rows = wrap.querySelectorAll(selector);
            rows.forEach(function (btn) {
                btn.style.display = rows.length > 1 ? 'block' : 'none';
            });
        }
    })();
</script>
@endpush
