@extends('layouts.app')

@section('title', 'Dashboard Super Admin')

@php
    $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
@endphp

@push('styles')
<style>
.sa-head-judul { font-size: 18px; font-weight: 700; margin: 0 0 16px; }
.sa-lihat-semua { color: var(--accent); font-weight: 600; display: inline-block; margin-top: 10px; font-size: 12.5px; }
.sa-card + .sa-card { margin-top: 16px; }
.sa-card + .dashboard-grid-2col { margin-top: 16px; }
.dashboard-grid-2col .sa-card + .sa-card { margin-top: 0; }
</style>
@endpush

@section('content')
<h1 class="sa-head-judul">Dashboard</h1>

<div class="kpi-grid">
    <x-kpi label="Menunggu keputusan" :angka="$menungguKeputusan" :href="route('admin.projects.index', ['tahap' => 'menunggu_acc'])" :mendesak="$menungguKeputusan > 0 && $pengajuanMendesak" :accent="'#D97706'" />
    <x-kpi label="Tugas staf tertunda" :angka="$stafTertunda" :sub="'di '.($pantauAdmin->count() + $tugasKorlap->count()).' staf'" :href="route('super-admin.monitoring.admin')" :mendesak="$stafMendesak" :accent="'#DC2626'" />
    <x-kpi label="Proyek berjalan" :angka="$proyekBerjalan" :pertumbuhan="$pertumbuhanBerjalan" :href="route('admin.projects.index', ['tahap' => 'berjalan', 'dari' => $dari->format('Y-m-d'), 'sampai' => $sampai->format('Y-m-d')])" />
    <x-kpi label="Invoice belum lunas" :angka="$invoiceBelumLunas->count()" :sub="$rp($invoiceTotal)" :href="route('admin.projects.index')" />
</div>

{{-- Ikhtisar Ekosistem & Finansial Sistem --}}
<div class="dash-pulse-strip">
    <div class="dash-stat-card" tabindex="0">
        <div>
            <div style="font-size: 11.5px; letter-spacing: 0.02em; color: var(--text-muted); font-weight: 600;">
                <i class="ti ti-users"></i> Populasi Akun
            </div>
            @php
                $totPop = max(1, $statPopulasi['total']);
                $extrasLen = round(($statPopulasi['extras_total'] / $totPop) * 188.5, 1);
                $clientLen = round(($statPopulasi['client_total'] / $totPop) * 188.5, 1);
                $timTotal = $statPopulasi['admin_total'] + $statPopulasi['korlap_total'] + $statPopulasi['super_admin_total'];
                $timLen = max(0, round(188.5 - $extrasLen - $clientLen, 1));
            @endphp
            <div class="dash-viz-body">
                <div class="viz-donut-wrap">
                    <svg class="viz-donut-svg" viewBox="0 0 76 76" aria-hidden="true">
                        <circle cx="38" cy="38" r="30" stroke="var(--surface-2, rgba(0,0,0,0.06))" stroke-width="7" fill="none" />
                        <circle cx="38" cy="38" r="30" stroke="#15803D" stroke-width="7" stroke-dasharray="{{ $extrasLen }} 188.5" stroke-dashoffset="0" fill="none" />
                        <circle cx="38" cy="38" r="30" stroke="#D97706" stroke-width="7" stroke-dasharray="{{ $clientLen }} 188.5" stroke-dashoffset="{{ -$extrasLen }}" fill="none" />
                        <circle cx="38" cy="38" r="30" stroke="#0284C7" stroke-width="7" stroke-dasharray="{{ $timLen }} 188.5" stroke-dashoffset="{{ -($extrasLen + $clientLen) }}" fill="none" />
                    </svg>
                    <div class="viz-center-label">
                        <span class="viz-center-num">{{ number_format($statPopulasi['total']) }}</span>
                        <span class="viz-center-sub">Akun</span>
                    </div>
                </div>
                <div class="viz-legend">
                    <div class="viz-legend-row">
                        <span class="viz-legend-label"><span class="viz-dot" style="background: #15803D;"></span>Talent</span>
                        <strong class="viz-legend-val">{{ $statPopulasi['extras_total'] }}</strong>
                    </div>
                    <div class="viz-legend-row">
                        <span class="viz-legend-label"><span class="viz-dot" style="background: #D97706;"></span>Client</span>
                        <strong class="viz-legend-val">{{ $statPopulasi['client_total'] }}</strong>
                    </div>
                    <div class="viz-legend-row">
                        <span class="viz-legend-label"><span class="viz-dot" style="background: #0284C7;"></span>Tim</span>
                        <strong class="viz-legend-val">{{ $timTotal }}</strong>
                    </div>
                </div>
            </div>
        </div>
        <span class="info-hint" title="Klik untuk rincian / kunci pop-up"><i class="ti ti-info-circle"></i></span>
        <div class="dash-hover-popover">
            <div class="dash-popover-header">
                <span class="dash-popover-title">Distribusi Populasi Pengguna</span>
                <button type="button" class="dash-popover-close" aria-label="Tutup"><i class="ti ti-x"></i></button>
            </div>
            <div class="popover-row"><span>Extras (Talent)</span><strong>{{ $statPopulasi['extras_total'] }}</strong></div>
            <div class="popover-row"><span>Siap Kontrak (NIK)</span><strong>{{ $statPopulasi['siap_kontrak'] }}</strong></div>
            <div class="popover-row"><span>Distribusi Grade</span><span>A: {{ $statPopulasi['grade_a'] }} · B: {{ $statPopulasi['grade_b'] }} · C: {{ $statPopulasi['grade_c'] }} · -: {{ $statPopulasi['grade_belum'] }}</span></div>
            <div class="popover-row"><span>Client / PH</span><strong>{{ $statPopulasi['client_total'] }}</strong></div>
            <div class="popover-row"><span>Staf Internal</span><span>Admin: {{ $statPopulasi['admin_total'] }} · Korlap: {{ $statPopulasi['korlap_total'] }} · SA: {{ $statPopulasi['super_admin_total'] }}</span></div>
            <div style="margin-top: 10px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                <a href="{{ route('super-admin.akun.index') }}" class="btn btn-sm btn-tonal" style="width: 100%; justify-content: center; font-size: 12px; gap: 6px;">Kelola Semua Akun <i class="ti ti-arrow-right"></i></a>
            </div>
        </div>
    </div>

    <div class="dash-stat-card" tabindex="0">
        <div>
            <div style="font-size: 11.5px; letter-spacing: 0.02em; color: var(--text-muted); font-weight: 600;">
                <i class="ti ti-movie"></i> Ekosistem Proyek
            </div>
            @php
                $persenKuota = min(100, max(0, $statProyekKuota['fulfillment_rate']));
                $ringLen = round(($persenKuota / 100) * 188.5, 1);
                $ringColor = $persenKuota >= 75 ? '#15803D' : ($persenKuota >= 40 ? '#D97706' : '#DC2626');
                $proyekAktifJalan = $statProyekKuota['shooting'] + $statProyekKuota['seleksi'];
            @endphp
            <div class="dash-viz-body">
                <div class="viz-ring-wrap" title="Keterisian Kuota: {{ $persenKuota }}%">
                    <svg class="viz-ring-svg" viewBox="0 0 76 76" aria-hidden="true">
                        <circle cx="38" cy="38" r="30" stroke="var(--surface-2, rgba(0,0,0,0.06))" stroke-width="7" fill="none" />
                        <circle cx="38" cy="38" r="30" stroke="{{ $ringColor }}" stroke-width="7" stroke-dasharray="{{ $ringLen }} 188.5" stroke-linecap="round" fill="none" />
                    </svg>
                    <div class="viz-center-label">
                        <span class="viz-center-num">{{ $persenKuota }}%</span>
                        <span class="viz-center-sub">Kuota</span>
                    </div>
                </div>
                <div class="viz-legend">
                    <div style="font-size: 13px; font-weight: 700; color: var(--text-primary); margin-bottom: 2px;">
                        {{ $statProyekKuota['total'] }} Proyek
                    </div>
                    <div class="viz-legend-row">
                        <span class="viz-legend-label"><span class="viz-dot" style="background: #15803D;"></span>Berjalan</span>
                        <strong class="viz-legend-val">{{ $proyekAktifJalan }}</strong>
                    </div>
                    <div class="viz-legend-row">
                        <span class="viz-legend-label"><span class="viz-dot" style="background: #D97706;"></span>Menunggu ACC</span>
                        <strong class="viz-legend-val">{{ $statProyekKuota['menunggu_acc'] }}</strong>
                    </div>
                    <div class="viz-legend-row">
                        <span class="viz-legend-label"><span class="viz-dot" style="background: #64748B;"></span>Selesai</span>
                        <strong class="viz-legend-val">{{ $statProyekKuota['selesai'] }}</strong>
                    </div>
                </div>
            </div>
        </div>
        <span class="info-hint" title="Klik untuk rincian / kunci pop-up"><i class="ti ti-info-circle"></i></span>
        <div class="dash-hover-popover">
            <div class="dash-popover-header">
                <span class="dash-popover-title">Status Siklus Proyek</span>
                <button type="button" class="dash-popover-close" aria-label="Tutup"><i class="ti ti-x"></i></button>
            </div>
            <div class="popover-row"><span>Menunggu ACC Super Admin</span><strong>{{ $statProyekKuota['menunggu_acc'] }}</strong></div>
            <div class="popover-row"><span>Buka Lowongan & Seleksi</span><strong>{{ $statProyekKuota['seleksi'] }}</strong></div>
            <div class="popover-row"><span>Shooting Berjalan</span><strong>{{ $statProyekKuota['shooting'] }}</strong></div>
            <div class="popover-row"><span>Selesai Produksi</span><strong>{{ $statProyekKuota['selesai'] }}</strong></div>
            <div class="popover-row"><span>Ditolak / Dibatalkan</span><strong>{{ $statProyekKuota['batal'] }}</strong></div>
            <div class="popover-row"><span>Keterisian Kursi Aktif</span><strong>{{ $statProyekKuota['terisi_kuota'] }} / {{ $statProyekKuota['target_kuota'] }}</strong></div>
            <div style="margin-top: 10px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                <a href="{{ route('admin.projects.index') }}" class="btn btn-sm btn-tonal" style="width: 100%; justify-content: center; font-size: 12px; gap: 6px;">Buka Kelola Proyek <i class="ti ti-arrow-right"></i></a>
            </div>
        </div>
    </div>

    <div class="dash-stat-card" tabindex="0">
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2px;">
                <div style="font-size: 11.5px; letter-spacing: 0.02em; color: var(--text-muted); font-weight: 600;">
                    <i class="ti ti-receipt-2"></i> Finansial &amp; Margin
                </div>
                <span class="badge" style="background: rgba(21, 128, 61, 0.12); color: #15803D; font-weight: 700; font-size: 11px; padding: 2px 7px;">
                    Margin {{ $statKeuangan['persen_margin'] }}%
                </span>
            </div>
            @php
                $totInv = max(1, $statKeuangan['total_invoice']);
                $persenLunas = round(($statKeuangan['invoice_lunas'] / $totInv) * 100);
                $persenPiutang = max(0, 100 - $persenLunas);
                $totBeban = $statKeuangan['payout_extras'] + $statKeuangan['biaya_operasional'];
                $persenBeban = min(100, round(($totBeban / $totInv) * 100));
                $persenMarginKotor = min(100, max(0, $statKeuangan['persen_margin']));
            @endphp
            <div style="font-size: var(--fs-xl, 22px); font-weight: 700; color: #16A34A; margin: 2px 0 1px;">
                {{ $rp($statKeuangan['margin_kotor']) }}
            </div>
            <div style="font-size: 11.5px; color: var(--text-muted); margin-bottom: 6px;">
                Omset {{ $rp($statKeuangan['total_invoice']) }} &middot; Beban {{ $rp($totBeban) }}
            </div>
            <div class="viz-fin-bars">
                <div class="viz-fin-item">
                    <div class="viz-fin-head">
                        <span style="color: var(--text-secondary);">Tagihan Client</span>
                        <strong style="color: var(--text-muted);">{{ $persenLunas }}% lunas</strong>
                    </div>
                    <div class="viz-fin-track" title="Lunas: {{ $persenLunas }}%, Piutang: {{ $persenPiutang }}%">
                        <div class="viz-fin-fill" style="width: {{ $persenLunas }}%; background: #15803D;"></div>
                        <div class="viz-fin-fill" style="width: {{ $persenPiutang }}%; background: #F59E0B;"></div>
                    </div>
                </div>
                <div class="viz-fin-item">
                    <div class="viz-fin-head">
                        <span style="color: var(--text-secondary);">Beban Produksi</span>
                        <strong style="color: var(--text-muted);">{{ $rp($totBeban) }}</strong>
                    </div>
                    <div class="viz-fin-track">
                        <div class="viz-fin-fill" style="width: {{ $persenBeban }}%; background: #EA580C;"></div>
                    </div>
                </div>
            </div>
        </div>
        <span class="info-hint" title="Klik untuk rincian / kunci pop-up"><i class="ti ti-info-circle"></i></span>
        <div class="dash-hover-popover">
            <div class="dash-popover-header">
                <span class="dash-popover-title">Rincian Arus Kas &amp; Margin</span>
                <button type="button" class="dash-popover-close" aria-label="Tutup"><i class="ti ti-x"></i></button>
            </div>
            <div class="popover-row"><span>Invoice Lunas (Kas Masuk)</span><strong style="color: #15803D;">{{ $rp($statKeuangan['invoice_lunas']) }}</strong></div>
            <div class="popover-row"><span>Piutang Client (Belum Lunas)</span><strong style="color: #D97706;">{{ $rp($statKeuangan['invoice_piutang']) }}</strong></div>
            <div class="popover-row"><span>Total Payout Extras</span><strong>{{ $rp($statKeuangan['payout_extras']) }}</strong></div>
            <div class="popover-row"><span>Pengeluaran Proyek</span><strong>{{ $rp($statKeuangan['biaya_operasional']) }}</strong></div>
            <div class="popover-row"><span>Estimasi Margin Kotor</span><strong>{{ $rp($statKeuangan['margin_kotor']) }} ({{ $statKeuangan['persen_margin'] }}%)</strong></div>
            <div style="margin-top: 10px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                <a href="{{ route('admin.projects.index') }}" class="btn btn-sm btn-tonal" style="width: 100%; justify-content: center; font-size: 12px; gap: 6px;">Lihat Proyek & Tagihan <i class="ti ti-arrow-right"></i></a>
            </div>
        </div>
    </div>

    <div class="dash-stat-card is-right-aligned" tabindex="0">
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2px;">
                <div style="font-size: 11.5px; letter-spacing: 0.02em; color: var(--text-muted); font-weight: 600;">
                    <i class="ti ti-filter"></i> Funnel Partisipasi
                </div>
                <span style="font-size: 11px; font-weight: 700; color: var(--text-secondary);">
                    {{ number_format($statFunnelKandidat['total']) }} Lamaran
                </span>
            </div>
            @php
                $totFun = max(1, $statFunnelKandidat['total']);
                $wAwal = max(6, round(($statFunnelKandidat['tahap_awal'] / $totFun) * 100));
                $wNego = max(6, round(($statFunnelKandidat['tahap_nego'] / $totFun) * 100));
                $wRev = max(6, round(($statFunnelKandidat['tahap_review'] / $totFun) * 100));
                $wKon = max(6, round(($statFunnelKandidat['tahap_kontrak'] / $totFun) * 100));
                $wSel = max(6, round(($statFunnelKandidat['tahap_selesai'] / $totFun) * 100));
            @endphp
            <div class="viz-funnel">
                <div class="viz-funnel-row">
                    <span class="viz-funnel-label">Awal/Review</span>
                    <div class="viz-funnel-track"><div class="viz-funnel-bar" style="width: {{ $wAwal }}%; background: #64748B;"></div></div>
                    <span class="viz-funnel-count">{{ $statFunnelKandidat['tahap_awal'] }}</span>
                </div>
                <div class="viz-funnel-row">
                    <span class="viz-funnel-label">Nego Fee</span>
                    <div class="viz-funnel-track"><div class="viz-funnel-bar" style="width: {{ $wNego }}%; background: #D97706;"></div></div>
                    <span class="viz-funnel-count">{{ $statFunnelKandidat['tahap_nego'] }}</span>
                </div>
                <div class="viz-funnel-row">
                    <span class="viz-funnel-label">Ke Client</span>
                    <div class="viz-funnel-track"><div class="viz-funnel-bar" style="width: {{ $wRev }}%; background: #0284C7;"></div></div>
                    <span class="viz-funnel-count">{{ $statFunnelKandidat['tahap_review'] }}</span>
                </div>
                <div class="viz-funnel-row">
                    <span class="viz-funnel-label">Kontrak</span>
                    <div class="viz-funnel-track"><div class="viz-funnel-bar" style="width: {{ $wKon }}%; background: #15803D;"></div></div>
                    <span class="viz-funnel-count">{{ $statFunnelKandidat['tahap_kontrak'] }}</span>
                </div>
                <div class="viz-funnel-row">
                    <span class="viz-funnel-label">Selesai</span>
                    <div class="viz-funnel-track"><div class="viz-funnel-bar" style="width: {{ $wSel }}%; background: #16A34A;"></div></div>
                    <span class="viz-funnel-count">{{ $statFunnelKandidat['tahap_selesai'] }}</span>
                </div>
            </div>
        </div>
        <span class="info-hint" title="Klik untuk rincian / kunci pop-up"><i class="ti ti-info-circle"></i></span>
        <div class="dash-hover-popover">
            <div class="dash-popover-header">
                <span class="dash-popover-title">Distribusi Tahapan Kandidat</span>
                <button type="button" class="dash-popover-close" aria-label="Tutup"><i class="ti ti-x"></i></button>
            </div>
            <div class="popover-row"><span>Tahap Awal (Diundang/Diajukan)</span><strong>{{ $statFunnelKandidat['tahap_awal'] }}</strong></div>
            <div class="popover-row"><span>Tahap Negosiasi Honor (Nego)</span><strong>{{ $statFunnelKandidat['tahap_nego'] }}</strong></div>
            <div class="popover-row"><span>Tahap Review Client (Deal/Ajukan)</span><strong>{{ $statFunnelKandidat['tahap_review'] }}</strong></div>
            <div class="popover-row"><span>Lolos & Kontrak Aktif</span><strong>{{ $statFunnelKandidat['tahap_kontrak'] }}</strong></div>
            <div class="popover-row"><span>Selesai Produksi</span><strong>{{ $statFunnelKandidat['tahap_selesai'] }}</strong></div>
            <div style="margin-top: 10px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                <a href="{{ route('admin.projects.index') }}" class="btn btn-sm btn-tonal" style="width: 100%; justify-content: center; font-size: 12px; gap: 6px;">Buka Seleksi & Lamaran <i class="ti ti-arrow-right"></i></a>
            </div>
        </div>
    </div>
</div>

<div class="card sa-card" style="border-top: 4px solid var(--warning, #D97706); margin: 16px 0 0;">
    <div class="card-title" style="display: flex; align-items: center; gap: 6px;">
        <span><i class="ti ti-alert-circle"></i> Butuh keputusan Anda</span>
        @if ($jumlahTindakan)
            <span class="badge badge-urgent" style="margin-left: auto;">{{ $jumlahTindakan }} perlu tindakan</span>
        @endif
    </div>
    @forelse ($butuhKeputusan as $item)
        @include('super-admin.dashboard._butuh-keputusan', ['item' => $item])
    @empty
        <x-kosong ikon="circle-check" teks="Tidak ada yang perlu diputuskan." />
    @endforelse
    @if ($jumlahTindakan)
        <a href="{{ route('admin.projects.index', ['tahap' => 'menunggu_acc']) }}" class="sa-lihat-semua">Lihat semua ({{ $jumlahTindakan }}) <i class="ti ti-arrow-right"></i></a>
    @endif
</div>

<div class="sa-command-grid">
    <div class="card sa-card" style="border-top: 4px solid var(--accent, #15803D); margin: 0;">
        <div class="card-title" style="display: flex; align-items: center; gap: 6px;">
            <span><i class="ti ti-bell"></i> Staf perlu diingatkan</span>
            @if ($stafTertunda)
                <span class="badge badge-netral" style="margin-left: auto;">{{ $stafTertunda }} tugas</span>
            @endif
        </div>
        @forelse ($diingatkan as $o)
            @include('super-admin.dashboard._diingatkan', ['o' => $o])
        @empty
            <x-kosong teks="Semua Admin dan Korlap sesuai jadwal." />
        @endforelse
        @if ($stafTertunda)
            @php $tujuanStaf = ($diingatkan->first()['peran'] ?? 'admin') === 'korlap' ? 'super-admin.monitoring.korlap' : 'super-admin.monitoring.admin'; @endphp
            <a href="{{ route($tujuanStaf) }}" class="sa-lihat-semua">Lihat semua ({{ $stafTertunda }}) <i class="ti ti-arrow-right"></i></a>
        @endif
    </div>

    <div class="card sa-card" style="margin: 0;">
        <div class="card-title"><i class="ti ti-calendar-event"></i> Jadwal &amp; Agenda Shooting</div>
        <x-navigasi-timeline :dari="$dariJadwal" :sampai="$sampaiJadwal" :skala="$skala" :action="route('super-admin.dashboard')" />
        <x-agenda :events="$agendaEvents" />
        <a href="{{ route('admin.projects.index') }}" class="sa-lihat-semua">Lihat semua{{ $totalHariJadwal > 5 ? ' ('.$totalHariJadwal.')' : '' }} <i class="ti ti-arrow-right"></i></a>
    </div>
</div>

{{-- Aktivitas Sistem Terbaru --}}
<div class="card sa-card" style="margin-top: 16px;">
    <div class="card-title" style="display: flex; justify-content: space-between; align-items: center;">
        <span><i class="ti ti-activity"></i> Aktivitas Sistem Terbaru</span>
        <a href="{{ route('super-admin.activity-logs') }}" class="btn btn-sm btn-tonal" style="font-size: 12px; padding: 4px 10px;">Semua Log <i class="ti ti-arrow-right"></i></a>
    </div>
    @if ($aktivitasTerbaru->isNotEmpty())
        <div class="dash-mini-feed">
            @foreach ($aktivitasTerbaru as $log)
                <div class="dash-mini-feed-item">
                    <div class="dash-mini-feed-icon" style="background: var(--surface-2, rgba(0,0,0,0.04)); color: var(--accent, #15803D);">
                        <i class="ti ti-point"></i>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; justify-content: space-between; gap: 8px; align-items: baseline; flex-wrap: wrap;">
                            <div>
                                <strong style="font-size: 13px;">{{ $log->user?->name ?? 'Sistem' }}</strong>
                                @if ($log->role)
                                    <span class="badge badge-netral" style="font-size: 10px; padding: 1px 6px; margin-left: 4px;">{{ $log->role }}</span>
                                @endif
                            </div>
                            <span style="font-size: 11.5px; color: var(--text-muted);">{{ $log->created_at?->diffForHumans() }}</span>
                        </div>
                        <div style="font-size: 12.5px; color: var(--text-secondary); margin-top: 3px;">
                            <span class="badge badge-info" style="font-size: 10.5px; padding: 1px 6px;">{{ \App\Models\ActivityLog::actionLabel($log->action) }}</span>
                            <span style="margin-left: 4px;">{{ $log->description ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <x-kosong ikon="activity" teks="Belum ada catatan aktivitas sistem." />
    @endif
</div>
@endsection
