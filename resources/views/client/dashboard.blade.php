@extends('layouts.app')

@section('title', 'Dashboard Client')

@section('content')
<div class="card-header-row" style="margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
    <div>
        <span class="label-editorial-sm">Ruang Kerja Produksi</span>
        <div class="font-editorial" style="font-size: 22px; font-weight: 700;">Dashboard Client</div>
        <div style="font-size: 13.5px; color: var(--text-secondary); margin-top: 2px;">Halo, <strong class="font-editorial">{{ auth()->user()->name }}</strong>{{ auth()->user()->nama_perusahaan ? ' · '.auth()->user()->nama_perusahaan : '' }} · Pantau proses casting dan pengajuan proyek Anda.</div>
    </div>
    <a href="{{ route('client.projects.request') }}" class="btn btn-brand"><i class="ti ti-plus"></i> Ajukan Proyek Baru</a>
</div>

@if ($perluDireview > 0)
    <div class="greenlight-banner">
        <div style="display: flex; align-items: center; gap: 10px; font-size: 13.5px;">
            <i class="ti ti-sparkles" style="font-size: 20px; color: var(--accent-strong, #15803D);"></i>
            <div>
                <strong>{{ $perluDireview }} Kandidat Menunggu Keputusan Pemilihan Anda</strong> · Tinjau lookbook portofolio kandidat yang telah dikurasi tim Admin JBTB.
            </div>
        </div>
        <a href="{{ route('client.reviews.index') }}" class="btn btn-brand btn-sm">Buka Review Kandidat <i class="ti ti-arrow-right"></i></a>
    </div>
@endif

@php
    $statusPengajuan = ['draft' => ['Draft', 'badge-netral'], 'menunggu_acc' => ['Menunggu ACC tim JBTB', 'badge-pending'], 'disetujui' => ['Disetujui', 'badge-aktif'], 'ditolak' => ['Ditolak', 'badge-tolak'], 'perlu_revisi' => ['Perlu Revisi', 'badge-pending']];
    $pengajuanPerlu = $pengajuan->whereIn('client_request_status', ['ditolak', 'perlu_revisi']);
    $jumlahTindakan = $pengajuanPerlu->count() + ($perluDireview ? 1 : 0) + $invoiceMenungguTtd->count() + $jadwalKosong->count() + (auth()->user()->email ? 0 : 1);
@endphp

<div class="dash-tiga">
    <div class="card">
        <div class="card-title" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
            <span>Jadwal Shooting</span>
        </div>
        <x-navigasi-timeline :dari="$dari" :sampai="$sampai" :skala="$skala" :action="route('client.dashboard')" />
        <x-agenda :events="$agendaEvents" />
    </div>

    <div class="card dash-perlu {{ $jumlahTindakan ? '' : 'is-aman' }}">
        <div class="card-title">
            <i class="ti ti-clipboard-list"></i> Perlu Tindakan
            @if ($jumlahTindakan)
                <span class="badge badge-netral" style="margin-left: 8px;">{{ $jumlahTindakan }}</span>
            @endif
        </div>
        <div class="dash-aman" role="status" @if ($jumlahTindakan) hidden @endif><i class="ti ti-circle-check"></i> Semua aman · tidak ada yang perlu ditindak.</div>

        @if ($perluDireview)
            <a href="{{ route('client.reviews.index') }}" class="dash-row">
                <div>
                    <span class="badge badge-pending">Menunggu Dipilih</span>
                    <strong>{{ $perluDireview }} kandidat</strong>
                    <div class="dash-sub">Pilih kandidat yang lolos</div>
                </div>
                <span class="dash-sub">Buka <i class="ti ti-arrow-right"></i></span>
            </a>
        @endif

        @foreach ($invoiceMenungguTtd as $inv)
            <a href="{{ route('invoices.show', $inv->casting_project_id) }}" class="dash-row">
                <div>
                    <span class="badge badge-info">Tanda tangan invoice</span>
                    <strong>{{ $inv->castingProject?->nama_produksi }}</strong>
                    <div class="dash-sub">Invoice sudah ditandatangani JBTB, menunggu tanda tangan Anda</div>
                </div>
                <span class="dash-sub">Buka <i class="ti ti-arrow-right"></i></span>
            </a>
        @endforeach

        @foreach ($jadwalKosong as $projectId => $daftarJadwal)
            <a href="{{ route('client.jadwal.show', $projectId) }}" class="dash-row">
                <div>
                    <span class="badge badge-info">Isi jadwal</span>
                    <strong>{{ $daftarJadwal->first()->castingProject?->nama_produksi }}</strong>
                    <div class="dash-sub">{{ $daftarJadwal->count() }} tanggal shooting belum ada lokasinya</div>
                </div>
                <span class="dash-sub">Buka <i class="ti ti-arrow-right"></i></span>
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
                    <a href="{{ route('client.projects.request') }}" class="btn btn-sm btn-tonal"><i class="ti ti-copy"></i> Ajukan Proyek Serupa</a>
                @elseif ($req->client_request_status === 'perlu_revisi')
                    <a href="{{ route('client.projects.request.edit', $req) }}" class="btn btn-sm btn-brand">Ubah Pengajuan</a>
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

    @php
        $kpSum = $perluDireview + $approve + $reject;
        $kpTotal = max($kpSum, 1);
        $lenApprove = round(($approve / $kpTotal) * 188.5, 1);
        $lenReview = round(($perluDireview / $kpTotal) * 188.5, 1);
        $lenReject = max(0, round(188.5 - $lenApprove - $lenReview, 1));
    @endphp
    <div class="card">
        <div class="card-title">Keputusan Pemilihan Saya</div>
        <div class="dash-viz-body" style="margin-bottom: 12px;">
            <div class="viz-donut-wrap">
                <svg class="viz-donut-svg" viewBox="0 0 76 76" aria-hidden="true">
                    <circle cx="38" cy="38" r="30" stroke="var(--surface-2, rgba(0,0,0,0.06))" stroke-width="7" fill="none" />
                    <circle cx="38" cy="38" r="30" stroke="#15803D" stroke-width="7" stroke-dasharray="{{ $lenApprove }} 188.5" stroke-dashoffset="0" fill="none" />
                    <circle cx="38" cy="38" r="30" stroke="#D97706" stroke-width="7" stroke-dasharray="{{ $lenReview }} 188.5" stroke-dashoffset="{{ -$lenApprove }}" fill="none" />
                    <circle cx="38" cy="38" r="30" stroke="#DC2626" stroke-width="7" stroke-dasharray="{{ $lenReject }} 188.5" stroke-dashoffset="{{ -($lenApprove + $lenReview) }}" fill="none" />
                </svg>
                <div class="viz-center-label">
                    <span class="viz-center-num">{{ $kpSum }}</span>
                    <span class="viz-center-sub">Talent</span>
                </div>
            </div>
            <div class="viz-legend">
                <a href="{{ route('client.reviews.index', ['status' => 'approved']) }}" class="viz-legend-row" style="text-decoration: none;">
                    <span class="viz-legend-label"><span class="viz-dot" style="background: #15803D;"></span>Dipilih</span>
                    <strong class="viz-legend-val" style="color: #15803D;">{{ $approve }}</strong>
                </a>
                <a href="{{ route('client.reviews.index', ['status' => 'menunggu']) }}" class="viz-legend-row" style="text-decoration: none;">
                    <span class="viz-legend-label"><span class="viz-dot" style="background: #D97706;"></span>Perlu dipilih</span>
                    <strong class="viz-legend-val" style="color: #D97706;">{{ $perluDireview }}</strong>
                </a>
                <a href="{{ route('client.reviews.index', ['status' => 'rejected']) }}" class="viz-legend-row" style="text-decoration: none;">
                    <span class="viz-legend-label"><span class="viz-dot" style="background: #DC2626;"></span>Ditolak</span>
                    <strong class="viz-legend-val" style="color: #DC2626;">{{ $reject }}</strong>
                </a>
            </div>
        </div>
        <div class="kp-segbar" role="img" aria-label="Perlu dipilih {{ $perluDireview }}, Dipilih {{ $approve }}, Ditolak {{ $reject }}">
            <span style="width: {{ $perluDireview / $kpTotal * 100 }}%; background: #D97706;"></span>
            <span style="width: {{ $approve / $kpTotal * 100 }}%; background: #15803D;"></span>
            <span style="width: {{ $reject / $kpTotal * 100 }}%; background: #DC2626;"></span>
        </div>
        <a href="{{ route('client.reviews.index') }}" class="btn btn-brand" style="width: 100%; margin-top: 12px;">Buka Pemilihan Kandidat</a>

        {{-- Ringkasan Tagihan & Kontrak --}}
        <div style="margin-top: 16px; padding-top: 12px; border-top: 1px dashed var(--border-color);">
            <div class="dash-stat-card" tabindex="0" style="padding: 10px 12px; cursor: pointer;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11.5px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">
                        <span><i class="ti ti-receipt-2"></i> Ringkasan Tagihan &amp; Kontrak</span>
                        <i class="ti ti-info-circle" title="Klik untuk rincian / kunci pop-up"></i>
                    </div>
                    <div style="font-size: 16px; font-weight: 700; margin: 4px 0 2px; color: var(--text-primary);">
                        {{ $statBillingClient['total_invoice_count'] }} Dokumen Tagihan
                    </div>
                    @php
                        $totNominalInv = max(1, $statBillingClient['lunas_nominal'] + $statBillingClient['belum_lunas_nominal']);
                        $pctNominalLunas = round(($statBillingClient['lunas_nominal'] / $totNominalInv) * 100);
                        $pctNominalBelum = max(0, 100 - $pctNominalLunas);
                    @endphp
                    <div class="viz-fin-bars" style="margin-top: 6px;">
                        <div class="viz-fin-item">
                            <div class="viz-fin-head">
                                <span style="color: var(--text-secondary); font-size: 11px;">Lunas ({{ $statBillingClient['lunas_count'] }})</span>
                                <strong style="color: #15803D; font-size: 11px;">Rp {{ number_format($statBillingClient['lunas_nominal'], 0, ',', '.') }}</strong>
                            </div>
                            <div class="viz-fin-track">
                                <div class="viz-fin-fill" style="width: {{ $pctNominalLunas }}%; background: #15803D;"></div>
                            </div>
                        </div>
                        @if ($statBillingClient['belum_lunas_nominal'] > 0)
                            <div class="viz-fin-item">
                                <div class="viz-fin-head">
                                    <span style="color: var(--text-secondary); font-size: 11px;">Menunggu Bayar ({{ $statBillingClient['belum_lunas_count'] }})</span>
                                    <strong style="color: #D97706; font-size: 11px;">Rp {{ number_format($statBillingClient['belum_lunas_nominal'], 0, ',', '.') }}</strong>
                                </div>
                                <div class="viz-fin-track">
                                    <div class="viz-fin-fill" style="width: {{ $pctNominalBelum }}%; background: #D97706;"></div>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div style="font-size: 11.5px; color: var(--text-secondary); margin-top: 6px;">
                        {{ $statBillingClient['lunas_count'] }} Lunas · {{ $statBillingClient['belum_lunas_count'] }} Menunggu Pembayaran
                    </div>
                </div>
                <div class="dash-hover-popover">
                    <div class="dash-popover-header">
                        <span class="dash-popover-title">Status Tagihan Proyek</span>
                        <button type="button" class="dash-popover-close" aria-label="Tutup"><i class="ti ti-x"></i></button>
                    </div>
                    <div class="popover-row"><span>Invoice Lunas ({{ $statBillingClient['lunas_count'] }})</span><strong style="color: #15803D;">Rp {{ number_format($statBillingClient['lunas_nominal'], 0, ',', '.') }}</strong></div>
                    <div class="popover-row"><span>Menunggu Bayar ({{ $statBillingClient['belum_lunas_count'] }})</span><strong style="color: #D97706;">Rp {{ number_format($statBillingClient['belum_lunas_nominal'], 0, ',', '.') }}</strong></div>
                    <div class="popover-row"><span>Belum Terbit</span><strong>{{ $statBillingClient['belum_terbit_count'] }} Proyek</strong></div>
                    <div style="margin-top: 8px; text-align: right; padding-top: 6px; border-top: 1px dashed var(--border-color);">
                        <a href="{{ route('invoices.index-client') }}" class="btn btn-sm btn-tonal" style="width: 100%; justify-content: center; font-size: 11px; padding: 4px 8px; gap: 4px;">Lihat Semua Tagihan <i class="ti ti-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
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
            <a href="{{ route('client.projects.request') }}" class="btn btn-sm btn-tonal" style="margin-top: 10px;"><i class="ti ti-copy"></i> Gunakan Sebagai Template / Proyek Baru</a>
        @endif
    </div>

    <div class="card">
        <div class="card-title">Proyek Berjalan</div>
        @forelse ($proyekBerjalan as $p)
            @php
                $tahapP = $p->tahap();
                $stepIndex = match ($tahapP) {
                    'menunggu_acc' => 1,
                    'mendatang' => 2,
                    'berjalan' => 3,
                    'selesai' => 4,
                    default => 2,
                };
            @endphp
            <div class="dash-row" style="flex-direction: column; align-items: stretch; gap: 8px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                    <span>{{ $p->nama_produksi }} <span class="kode-proyek">{{ $p->kode_proyek }}</span>
                        @if ($tahapP)<span class="badge {{ \App\Models\CastingProject::TAHAP_BADGES[$tahapP] }}">{{ \App\Models\CastingProject::TAHAP[$tahapP] }}</span>@endif
                    </span>
                    <span class="dash-sub">Batas pendaftaran: {{ \Carbon\Carbon::parse($p->deadline)->translatedFormat('d M Y') }}</span>
                </div>
                <div class="mini-pipeline-tracker" aria-label="Pipeline Tahapan Proyek">
                    <div class="mini-pipeline-step {{ $stepIndex > 1 ? 'is-done' : ($stepIndex === 1 ? 'is-current' : '') }}" title="1. Brief Diajukan"></div>
                    <div class="mini-pipeline-step {{ $stepIndex > 2 ? 'is-done' : ($stepIndex === 2 ? 'is-current' : '') }}" title="2. Seleksi & Pemilihan"></div>
                    <div class="mini-pipeline-step {{ $stepIndex > 3 ? 'is-done' : ($stepIndex === 3 ? 'is-current' : '') }}" title="3. Shooting"></div>
                    <div class="mini-pipeline-step {{ $stepIndex >= 4 ? 'is-done' : '' }}" title="4. Selesai & Invoice"></div>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--text-muted);">
                    <span>1. Brief</span>
                    <span style="{{ $stepIndex === 2 ? 'font-weight: 600; color: var(--accent);' : '' }}">2. Seleksi</span>
                    <span style="{{ $stepIndex === 3 ? 'font-weight: 600; color: var(--accent);' : '' }}">3. Shooting</span>
                    <span style="{{ $stepIndex === 4 ? 'font-weight: 600; color: var(--accent);' : '' }}">4. Selesai</span>
                </div>
                {{-- Indikator Kuota & Shortcut Review --}}
                <div style="margin-top: 4px; padding: 6px 10px; background: var(--surface-1, rgba(0,0,0,0.02)); border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; margin-bottom: 4px;">
                        <span>Kebutuhan Talent: <strong>{{ $p->kuota_terpilih }}/{{ $p->kuota_target }} Terpilih</strong></span>
                        <span style="font-size: 11.5px; font-weight: 600;">{{ $p->persen_kuota }}%</span>
                    </div>
                    <div class="dash-segmented-bar" style="height: 6px; margin: 3px 0 6px;">
                        <div class="seg" style="width: {{ $p->persen_kuota }}%; background: var(--accent, #15803D);"></div>
                        <div class="seg" style="width: {{ 100 - $p->persen_kuota }}%; background: var(--surface-2, rgba(0,0,0,0.06));"></div>
                    </div>
                    @if ($p->kandidat_menunggu > 0)
                        <div style="display: flex; justify-content: flex-end; margin-top: 4px;">
                            <a href="{{ route('client.reviews.show', $p) }}" class="btn btn-sm btn-brand" style="font-size: 11.5px; padding: 3px 10px;">
                                <i class="ti ti-user-check"></i> Pilih Kandidat ({{ $p->kandidat_menunggu }}) <i class="ti ti-arrow-right"></i>
                            </a>
                        </div>
                    @endif
                </div>
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
