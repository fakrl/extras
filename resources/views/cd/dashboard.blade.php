@extends('layouts.app')

@section('title', 'Dashboard Client')

@section('content')
<p style="color: var(--text-secondary); margin: -8px 0 20px; font-size: 13.5px;">
    Halo, {{ auth()->user()->name }}.
</p>

@unless (auth()->user()->email)
    <div class="card" id="kartu-lengkapi-profil" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap;">
        <span>Lengkapi profil (email buat notifikasi &amp; lupa password).</span>
        <span style="display: flex; gap: 8px;">
            <a href="{{ route('cd.profil') }}" class="btn btn-brand">Lengkapi</a>
            <button type="button" class="btn" onclick="try { localStorage.setItem('tutup_lengkapi_profil', '1'); } catch (e) {} this.closest('.card').remove();">Tutup</button>
        </span>
    </div>
    <script>try { if (localStorage.getItem('tutup_lengkapi_profil')) document.getElementById('kartu-lengkapi-profil').remove(); } catch (e) {}</script>
@endunless

<div class="card" style="margin-bottom: 20px;">
    <div class="card-title">Pengajuan Anda</div>
    @php $statusPengajuan = ['draft' => ['Draft', 'badge-netral'], 'menunggu_acc' => ['Menunggu ACC tim JBTB', 'badge-pending'], 'disetujui' => ['Disetujui', 'badge-aktif'], 'ditolak' => ['Ditolak', 'badge-tolak']]; @endphp
    @forelse ($pengajuan as $req)
        @php [$labelReq, $badgeReq] = $statusPengajuan[$req->client_request_status] ?? [$req->client_request_status, 'badge-netral']; @endphp
        <div style="padding: 8px 0; border-bottom: 1px solid var(--border-color);">
            <div style="display: flex; justify-content: space-between; gap: 8px; flex-wrap: wrap;">
                <span>{{ $req->nama_produksi }} <span style="color: var(--text-muted); font-size: var(--fs-xs);">· {{ $req->created_at->format('d M Y') }}</span></span>
                <span class="badge {{ $badgeReq }}">{{ $labelReq }}</span>
            </div>
            @if ($req->client_request_status === 'ditolak' && $req->alasan_tolak)
                <div style="font-size: var(--fs-sm); color: var(--danger); margin-top: 4px;">Alasan: {{ $req->alasan_tolak }}</div>
            @endif
        </div>
    @empty
        <p style="color: var(--text-muted); font-size: 13px;">Anda belum pernah mengajukan proyek.</p>
        <a href="{{ route('cd.projects.request') }}" class="btn btn-brand">Ajukan Proyek Pertama</a>
    @endforelse
    @if ($pengajuan->isNotEmpty())
        <a href="{{ route('cd.projects.request') }}" class="btn" style="margin-top: 10px;">Ajukan Proyek Baru</a>
    @endif
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <div class="metric-card">
        <div class="metric-label">Perlu Direview</div>
        <div class="metric-value">{{ $perluDireview }}</div>
    </div>
</div>

<div class="card" style="margin-bottom: 20px;">
    <div class="card-title">Jadwal Shooting Bulan Ini</div>
    <x-jadwal-calendar :events="$jadwalBulanIni" :compact="true" />
</div>

<div class="dashboard-grid-2col is-even">
    <div class="card">
        <div class="card-title">Keputusan Greenlight Saya</div>
        <div class="chart-box"><canvas id="chartKeputusan"></canvas></div>
    </div>
    <div class="card" style="display: flex; align-items: center; justify-content: center;">
        <a href="{{ route('cd.reviews.index') }}" class="btn btn-brand">Buka Greenlight</a>
    </div>
</div>

<div class="card" style="margin-top: 16px;">
    <div class="card-title">Proyek Berjalan</div>
    @forelse ($proyekBerjalan as $p)
        <div style="padding: 6px 0; border-bottom: 1px solid var(--border-color);">
            {{ $p->nama_produksi }}
            <span style="color: var(--text-secondary); font-size: 12px; float: right;">Deadline: {{ \Carbon\Carbon::parse($p->deadline)->format('d M Y') }}</span>
        </div>
    @empty
        <p style="color: var(--text-muted); font-size: 13px;">Tidak ada proyek yang sedang berjalan.</p>
    @endforelse
</div>

<div class="card" style="margin-top: 16px;">
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
