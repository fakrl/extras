@extends('layouts.app')

@section('title', 'Dashboard Client')

@section('content')
<p style="color: var(--text-secondary); margin: -8px 0 20px; font-size: 13.5px;">
    Halo, {{ auth()->user()->name }}.
</p>

@php
    $statusPengajuan = ['draft' => ['Draft', 'badge-netral'], 'menunggu_acc' => ['Menunggu ACC tim JBTB', 'badge-pending'], 'disetujui' => ['Disetujui', 'badge-aktif'], 'ditolak' => ['Ditolak', 'badge-tolak']];
    $pengajuanPerlu = $pengajuan->whereIn('client_request_status', ['menunggu_acc', 'ditolak']);
    $jumlahTindakan = $pengajuanPerlu->count() + ($perluDireview ? 1 : 0) + (auth()->user()->email ? 0 : 1);
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
                <span class="badge badge-pending" style="margin-left: 8px;">{{ $jumlahTindakan }}</span>
            @endif
        </div>
        <div class="dash-aman" role="status" @if ($jumlahTindakan) hidden @endif><i class="ti ti-circle-check"></i> Semua aman &mdash; tidak ada yang perlu ditindak.</div>

        @if ($perluDireview)
            <a href="{{ route('cd.reviews.index') }}" class="dash-row">
                <div>
                    <span class="badge badge-pending">Menunggu Greenlight</span>
                    <strong>{{ $perluDireview }} kandidat</strong>
                    <div class="dash-sub">Pilih kandidat yang lolos</div>
                </div>
                <span class="dash-sub">Buka &rarr;</span>
            </a>
        @endif

        @foreach ($pengajuanPerlu as $req)
            @php [$labelReq, $badgeReq] = $statusPengajuan[$req->client_request_status]; @endphp
            <div class="dash-row">
                <div>
                    <span class="badge {{ $badgeReq }}">{{ $req->client_request_status === 'ditolak' ? 'Proyek Ditolak' : $labelReq }}</span>
                    <strong>{{ $req->nama_produksi }}</strong>
                    @if ($req->client_request_status === 'ditolak' && $req->alasan_tolak)
                        <div class="dash-sub" style="color: var(--danger);">Alasan: {{ $req->alasan_tolak }}</div>
                    @else
                        <div class="dash-sub">Diajukan {{ $req->created_at->format('d M Y') }}</div>
                    @endif
                </div>
                @if ($req->client_request_status === 'ditolak')
                    <a href="{{ route('cd.projects.request') }}" class="btn btn-sm">Ajukan Ulang</a>
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
                    <a href="{{ route('cd.profil') }}" class="btn btn-sm btn-brand">Lengkapi</a>
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
        <div class="card-title">Keputusan Greenlight Saya</div>
        <div class="dash-metrik" style="margin-bottom: 12px;">
            <a href="{{ route('cd.reviews.index') }}" class="metric-card" style="grid-column: span 2;">
                <div class="metric-label">Perlu Direview</div>
                <div class="metric-value">{{ $perluDireview }}</div>
            </a>
        </div>
        <div class="chart-box"><canvas id="chartKeputusan"></canvas></div>
        <a href="{{ route('cd.reviews.index') }}" class="btn btn-brand" style="width: 100%; margin-top: 12px;">Buka Greenlight</a>
    </div>
</div>

<div class="dashboard-grid-2col is-even">
    <div class="card">
        <div class="card-title">Pengajuan Anda</div>
        @forelse ($pengajuan as $req)
            @php [$labelReq, $badgeReq] = $statusPengajuan[$req->client_request_status] ?? [$req->client_request_status, 'badge-netral']; @endphp
            <div class="dash-row">
                <div>
                    {{ $req->nama_produksi }} <span class="dash-sub">· {{ $req->created_at->format('d M Y') }}</span>
                    @if ($req->client_request_status === 'ditolak' && $req->alasan_tolak)
                        <div style="font-size: var(--fs-sm); color: var(--danger); margin-top: 4px;">Alasan: {{ $req->alasan_tolak }}</div>
                    @endif
                </div>
                <span class="badge {{ $badgeReq }}">{{ $labelReq }}</span>
            </div>
        @empty
            <p style="color: var(--text-muted); font-size: 13px;">Anda belum pernah mengajukan proyek.</p>
            <a href="{{ route('cd.projects.request') }}" class="btn btn-brand">Ajukan Proyek Pertama</a>
        @endforelse
        @if ($pengajuan->isNotEmpty())
            <a href="{{ route('cd.projects.request') }}" class="btn" style="margin-top: 10px;">Ajukan Proyek Baru</a>
        @endif
    </div>

    <div class="card">
        <div class="card-title">Proyek Berjalan</div>
        @forelse ($proyekBerjalan as $p)
            <div class="dash-row">
                <span>{{ $p->nama_produksi }}</span>
                <span class="dash-sub">Deadline: {{ \Carbon\Carbon::parse($p->deadline)->format('d M Y') }}</span>
            </div>
        @empty
            <p style="color: var(--text-muted); font-size: 13px;">Tidak ada proyek yang sedang berjalan.</p>
        @endforelse
    </div>
</div>

<div class="card">
    <div class="card-title">Karakter &amp; Pendaftar</div>
    <div class="table-container">
    <table>
        <thead>
            <tr><th>Proyek</th><th>Karakter</th><th>Pendaftar</th></tr>
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
