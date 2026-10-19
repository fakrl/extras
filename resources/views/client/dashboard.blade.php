@extends('layouts.app')

@section('title', 'Dashboard Client')

@section('content')
<p style="color: var(--text-secondary); margin: -8px 0 20px; font-size: 13.5px;">
    Halo, {{ auth()->user()->name }}.
</p>

@php
    $statusPengajuan = ['draft' => ['Draft', 'badge-netral'], 'menunggu_acc' => ['Menunggu ACC tim JBTB', 'badge-pending'], 'disetujui' => ['Disetujui', 'badge-aktif'], 'ditolak' => ['Ditolak', 'badge-tolak'], 'perlu_revisi' => ['Perlu Revisi', 'badge-pending']];
    $pengajuanPerlu = $pengajuan->whereIn('client_request_status', ['ditolak', 'perlu_revisi']);
    $jumlahTindakan = $pengajuanPerlu->count() + ($perluDireview ? 1 : 0) + $invoiceMenungguTtd->count() + $jadwalKosong->count() + (auth()->user()->email ? 0 : 1);
@endphp

<div class="dash-tiga">
    <div class="card">
        <div class="card-title">Jadwal Shooting Bulan Ini</div>
        <x-jadwal-calendar :events="$jadwalBulanIni" :compact="true" />
    </div>

    <div class="card dash-perlu {{ $jumlahTindakan ? '' : 'is-aman' }}">
        <div class="card-title">
            <i class="ti ti-clipboard-list"></i> Perlu Tindakan
            @if ($jumlahTindakan)
                <span class="badge badge-netral" style="margin-left: 8px;">{{ $jumlahTindakan }}</span>
            @endif
        </div>
        <div class="dash-aman" role="status" @if ($jumlahTindakan) hidden @endif><i class="ti ti-circle-check"></i> Semua aman &mdash; tidak ada yang perlu ditindak.</div>

        @if ($perluDireview)
            <a href="{{ route('client.reviews.index') }}" class="dash-row">
                <div>
                    <span class="badge badge-pending">Menunggu Dipilih</span>
                    <strong>{{ $perluDireview }} kandidat</strong>
                    <div class="dash-sub">Pilih kandidat yang lolos</div>
                </div>
                <span class="dash-sub">Buka &rarr;</span>
            </a>
        @endif

        @foreach ($invoiceMenungguTtd as $inv)
            <a href="{{ route('invoices.show', $inv->casting_project_id) }}" class="dash-row">
                <div>
                    <span class="badge badge-info">Tanda tangan invoice</span>
                    <strong>{{ $inv->castingProject?->nama_produksi }}</strong>
                    <div class="dash-sub">Invoice sudah ditandatangani JBTB, menunggu tanda tangan Anda</div>
                </div>
                <span class="dash-sub">Buka &rarr;</span>
            </a>
        @endforeach

        @foreach ($jadwalKosong as $projectId => $daftarJadwal)
            <a href="{{ route('client.jadwal.show', $projectId) }}" class="dash-row">
                <div>
                    <span class="badge badge-info">Isi jadwal</span>
                    <strong>{{ $daftarJadwal->first()->castingProject?->nama_produksi }}</strong>
                    <div class="dash-sub">{{ $daftarJadwal->count() }} tanggal shooting belum ada lokasinya</div>
                </div>
                <span class="dash-sub">Buka &rarr;</span>
            </a>
        @endforeach

        @foreach ($pengajuanPerlu as $req)
            @php [$labelReq, $badgeReq] = $statusPengajuan[$req->client_request_status]; @endphp
            <div class="dash-row">
                <div>
                    <span class="badge {{ $badgeReq }}">{{ $req->client_request_status === 'ditolak' ? 'Proyek Ditolak' : $labelReq }}</span>
                    <strong>{{ $req->nama_produksi }}</strong>
                    @if (in_array($req->client_request_status, ['ditolak', 'perlu_revisi']) && $req->alasan_tolak)
                        <div class="dash-sub" style="color: var(--danger);">{{ $req->client_request_status === 'perlu_revisi' ? 'Catatan dari JBTB' : 'Alasan' }}: {{ $req->alasan_tolak }}</div>
                    @else
                        <div class="dash-sub">Diajukan {{ $req->created_at->translatedFormat('d M Y') }}</div>
                    @endif
                </div>
                @if ($req->client_request_status === 'ditolak')
                    <a href="{{ route('client.projects.request') }}" class="btn">Ajukan Ulang</a>
                @elseif ($req->client_request_status === 'perlu_revisi')
                    <a href="{{ route('client.projects.request.edit', $req) }}" class="btn btn-brand">Ubah</a>
                @endif
            </div>
        @endforeach

        @unless (auth()->user()->email)
            <div class="dash-row" id="kartu-lengkapi-profil">
                <div>
                    <span class="badge badge-info">Profil</span>
                    <strong>Lengkapi profil</strong>
                    <div class="dash-sub">Email buat notifikasi &amp; lupa password.</div>
                </div>
                <span style="display: flex; gap: 8px;">
                    <a href="{{ route('client.profil') }}" class="btn btn-brand">Lengkapi</a>
                    <button type="button" class="btn btn-sm" onclick="try { localStorage.setItem('tutup_lengkapi_profil', '1'); } catch (e) {} tutupLengkapiProfil();">Tutup</button>
                </span>
            </div>
        @endunless

    </div>
    @unless (auth()->user()->email)
        <script>
        function tutupLengkapiProfil() {
            var r = document.getElementById('kartu-lengkapi-profil');
            if (! r) return;
            var c = r.closest('.dash-perlu'), b = c.querySelector('.card-title .badge');
            r.remove();
            var n = c.querySelectorAll('.dash-row').length;
            if (b) b.textContent = n;
            if (! n) { c.classList.add('is-aman'); if (b) b.remove(); c.querySelector('.dash-aman').hidden = false; }
        }
        try { if (localStorage.getItem('tutup_lengkapi_profil')) tutupLengkapiProfil(); } catch (e) {}
        </script>
    @endunless

    <div class="card">
        <div class="card-title">Keputusan Pemilihan Saya</div>
        <div class="dash-metrik" style="margin-bottom: 12px;">
            <a href="{{ route('client.reviews.index') }}" class="metric-card" style="grid-column: span 2;">
                <div class="metric-label">Perlu Direview</div>
                <div class="metric-value">{{ $perluDireview }}</div>
            </a>
        </div>
        <div class="chart-box"><canvas id="chartKeputusan"></canvas></div>
        <a href="{{ route('client.reviews.index') }}" class="btn btn-brand" style="width: 100%; margin-top: 12px;">Buka Pemilihan Kandidat</a>
    </div>
</div>

<div class="dashboard-grid-2col is-even">
    <div class="card">
        <div class="card-title">Pengajuan Anda</div>
        @forelse ($pengajuan as $req)
            @php [$labelReq, $badgeReq] = $statusPengajuan[$req->client_request_status] ?? [$req->client_request_status, 'badge-netral']; @endphp
            <div class="dash-row">
                <div>
                    {{ $req->nama_produksi }} <span class="dash-sub">· {{ $req->created_at->translatedFormat('d M Y') }}</span>
                    @if ($req->client_request_status === 'ditolak' && $req->alasan_tolak)
                        <div style="font-size: var(--fs-sm); color: var(--danger); margin-top: 4px;">Alasan: {{ $req->alasan_tolak }}</div>
                    @endif
                </div>
                <span class="badge {{ $badgeReq }}">{{ $labelReq }}</span>
            </div>
        @empty
            <p style="color: var(--text-muted); font-size: 13px;">Anda belum pernah mengajukan proyek.</p>
            <a href="{{ route('client.projects.request') }}" class="btn btn-brand">Ajukan Proyek Pertama</a>
        @endforelse
        @if ($pengajuan->isNotEmpty())
            <a href="{{ route('client.projects.request') }}" class="btn" style="margin-top: 10px;">Ajukan Proyek Baru</a>
        @endif
    </div>

    <div class="card">
        <div class="card-title">Proyek Berjalan</div>
        @forelse ($proyekBerjalan as $p)
            @php $tahapP = $p->tahap(); @endphp
            <div class="dash-row">
                <span>{{ $p->nama_produksi }} <span class="kode-proyek">{{ $p->kode_proyek }}</span>
                    @if ($tahapP)<span class="badge {{ \App\Models\CastingProject::TAHAP_BADGES[$tahapP] }}">{{ \App\Models\CastingProject::TAHAP[$tahapP] }}</span>@endif
                </span>
                <span class="dash-sub">Batas pendaftaran: {{ \Carbon\Carbon::parse($p->deadline)->translatedFormat('d M Y') }}</span>
            </div>
        @empty
            <p style="color: var(--text-muted); font-size: 13px;">Tidak ada proyek yang sedang berjalan.</p>
        @endforelse
    </div>
</div>

<div class="card">
    <div class="card-title">Peran &amp; Pendaftar</div>
    <div class="table-container">
    <table>
        <thead>
            <tr><th>Proyek</th><th>Peran</th><th>Pendaftar</th></tr>
        </thead>
        <tbody>
            @forelse ($karakterPendaftar as $item)
                <tr>
                    <td>{{ $item['proyek'] }}</td>
                    <td>{{ $item['karakter'] }}</td>
                    <td>{{ $item['jumlah'] }}</td>
                </tr>
            @empty
                <tr><td colspan="3" style="text-align: center; color: var(--text-muted); padding: 16px 0;">Belum ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    var textColor = isDark ? '#9db3a2' : '#435449';

    new Chart(document.getElementById('chartKeputusan'), {
        type: 'doughnut',
        data: {
            labels: @json($chartKeputusan['labels']),
            datasets: [{
                data: @json($chartKeputusan['data']),
                backgroundColor: ['#22c55e', '#ef4444'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { color: textColor } } }
        }
    });
</script>
@endpush
