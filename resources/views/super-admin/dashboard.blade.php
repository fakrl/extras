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
    <x-kpi label="Proyek berjalan" :angka="$proyekBerjalan" :pertumbuhan="$pertumbuhanBerjalan" :href="route('admin.projects.index', ['tahap' => 'berjalan', 'dari' => $dari->format('Y-m-d'), 'sampai' => $sampai->format('Y-m-d')])" :accent="'#15803D'" />
    <x-kpi label="Invoice belum lunas" :angka="$invoiceBelumLunas->count()" :sub="$rp($invoiceTotal)" :href="route('admin.projects.index')" :mendesak="$invoiceBelumLunas->isNotEmpty()" :accent="'#2563EB'" />
</div>

{{-- Ikhtisar Ekosistem & Finansial Sistem --}}
<div class="dash-pulse-strip">
    <div class="dash-stat-card" tabindex="0">
        <a href="{{ route('super-admin.akun.index') }}" style="text-decoration: none; color: inherit;">
            <div style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                <i class="ti ti-users"></i> Populasi Akun
            </div>
            <div style="font-size: 22px; font-weight: 700; color: var(--text-primary); margin: 4px 0 2px;">
                {{ number_format($statPopulasi['total']) }}
            </div>
            <div style="font-size: 12px; color: var(--text-secondary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                {{ $statPopulasi['extras_total'] }} Talent · {{ $statPopulasi['client_total'] }} Client · {{ $statPopulasi['admin_total'] + $statPopulasi['korlap_total'] + $statPopulasi['super_admin_total'] }} Tim
            </div>
        </a>
        <span class="info-hint"><i class="ti ti-info-circle"></i></span>
        <div class="dash-hover-popover">
            <div style="font-weight: 700; font-size: 12px; margin-bottom: 6px; color: var(--text-primary);">Distribusi Populasi Pengguna</div>
            <div class="popover-row"><span>Extras (Talent)</span><strong>{{ $statPopulasi['extras_total'] }}</strong></div>
            <div class="popover-row"><span>Siap Kontrak (NIK)</span><strong>{{ $statPopulasi['siap_kontrak'] }}</strong></div>
            <div class="popover-row"><span>Distribusi Grade</span><span>A: {{ $statPopulasi['grade_a'] }} · B: {{ $statPopulasi['grade_b'] }} · C: {{ $statPopulasi['grade_c'] }} · -: {{ $statPopulasi['grade_belum'] }}</span></div>
            <div class="popover-row"><span>Client / PH</span><strong>{{ $statPopulasi['client_total'] }}</strong></div>
            <div class="popover-row"><span>Staf Internal</span><span>Admin: {{ $statPopulasi['admin_total'] }} · Korlap: {{ $statPopulasi['korlap_total'] }} · SA: {{ $statPopulasi['super_admin_total'] }}</span></div>
        </div>
    </div>

    <div class="dash-stat-card" tabindex="0">
        <a href="{{ route('admin.projects.index') }}" style="text-decoration: none; color: inherit;">
            <div style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                <i class="ti ti-movie"></i> Ekosistem Proyek
            </div>
            <div style="font-size: 22px; font-weight: 700; color: var(--text-primary); margin: 4px 0 2px;">
                {{ $statProyekKuota['total'] }} Proyek
            </div>
            <div class="dash-segmented-bar" title="Keterisian Kursi: {{ $statProyekKuota['fulfillment_rate'] }}%">
                <div class="seg" style="width: {{ $statProyekKuota['fulfillment_rate'] }}%; background: var(--accent, #15803D);"></div>
                <div class="seg" style="width: {{ 100 - $statProyekKuota['fulfillment_rate'] }}%; background: var(--surface-2, rgba(0,0,0,0.06));"></div>
            </div>
            <div style="font-size: 12px; color: var(--text-secondary);">
                Keterisian Kuota: <strong>{{ $statProyekKuota['fulfillment_rate'] }}%</strong> ({{ $statProyekKuota['terisi_kuota'] }}/{{ $statProyekKuota['target_kuota'] }})
            </div>
        </a>
        <span class="info-hint"><i class="ti ti-info-circle"></i></span>
        <div class="dash-hover-popover">
            <div style="font-weight: 700; font-size: 12px; margin-bottom: 6px; color: var(--text-primary);">Status Siklus Proyek</div>
            <div class="popover-row"><span>Menunggu ACC Super Admin</span><strong>{{ $statProyekKuota['menunggu_acc'] }}</strong></div>
            <div class="popover-row"><span>Buka Lowongan & Seleksi</span><strong>{{ $statProyekKuota['seleksi'] }}</strong></div>
            <div class="popover-row"><span>Shooting Berjalan</span><strong>{{ $statProyekKuota['shooting'] }}</strong></div>
            <div class="popover-row"><span>Selesai Produksi</span><strong>{{ $statProyekKuota['selesai'] }}</strong></div>
            <div class="popover-row"><span>Ditolak / Dibatalkan</span><strong>{{ $statProyekKuota['batal'] }}</strong></div>
            <div class="popover-row"><span>Keterisian Kursi Aktif</span><strong>{{ $statProyekKuota['terisi_kuota'] }} / {{ $statProyekKuota['target_kuota'] }}</strong></div>
        </div>
    </div>

    <div class="dash-stat-card" tabindex="0">
        <a href="{{ route('admin.projects.index') }}" style="text-decoration: none; color: inherit;">
            <div style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                <i class="ti ti-receipt-2"></i> Finansial & Margin
            </div>
            <div style="font-size: 22px; font-weight: 700; color: var(--text-primary); margin: 4px 0 2px;">
                {{ $rp($statKeuangan['total_invoice']) }}
            </div>
            @php
                $persenLunas = $statKeuangan['total_invoice'] > 0 ? round(($statKeuangan['invoice_lunas'] / $statKeuangan['total_invoice']) * 100) : 0;
            @endphp
            <div class="dash-segmented-bar" title="Lunas: {{ $persenLunas }}%">
                <div class="seg" style="width: {{ $persenLunas }}%; background: #15803D;"></div>
                <div class="seg" style="width: {{ 100 - $persenLunas }}%; background: #D97706;"></div>
            </div>
            <div style="font-size: 12px; color: var(--text-secondary);">
                Estimasi Margin: <strong>{{ $statKeuangan['persen_margin'] }}%</strong> ({{ $rp($statKeuangan['margin_kotor']) }})
            </div>
        </a>
        <span class="info-hint"><i class="ti ti-info-circle"></i></span>
        <div class="dash-hover-popover">
            <div style="font-weight: 700; font-size: 12px; margin-bottom: 6px; color: var(--text-primary);">Rincian Arus Kas & Margin</div>
            <div class="popover-row"><span>Invoice Lunas (Kas Masuk)</span><strong style="color: #15803D;">{{ $rp($statKeuangan['invoice_lunas']) }}</strong></div>
            <div class="popover-row"><span>Piutang Client (Belum Lunas)</span><strong style="color: #D97706;">{{ $rp($statKeuangan['invoice_piutang']) }}</strong></div>
            <div class="popover-row"><span>Total Payout Extras</span><strong>{{ $rp($statKeuangan['payout_extras']) }}</strong></div>
            <div class="popover-row"><span>Pengeluaran Proyek</span><strong>{{ $rp($statKeuangan['biaya_operasional']) }}</strong></div>
            <div class="popover-row"><span>Payroll Staf & Korlap</span><strong>Rp&nbsp;{{ number_format($statKeuangan['payroll_staf'], 0, ',', '.') }}</strong></div>
            <div class="popover-row"><span>Estimasi Margin Kotor</span><strong>{{ $rp($statKeuangan['margin_kotor']) }} ({{ $statKeuangan['persen_margin'] }}%)</strong></div>
        </div>
    </div>

    <div class="dash-stat-card" tabindex="0">
        <a href="{{ route('admin.projects.index') }}" style="text-decoration: none; color: inherit;">
            <div style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                <i class="ti ti-filter"></i> Funnel Partisipasi
            </div>
            <div style="font-size: 22px; font-weight: 700; color: var(--text-primary); margin: 4px 0 2px;">
                {{ number_format($statFunnelKandidat['total']) }} Lamaran
            </div>
            @php
                $totFun = max(1, $statFunnelKandidat['total']);
                $pAwal = round(($statFunnelKandidat['tahap_awal'] / $totFun) * 100);
                $pNego = round(($statFunnelKandidat['tahap_nego'] / $totFun) * 100);
                $pRev = round(($statFunnelKandidat['tahap_review'] / $totFun) * 100);
                $pKon = round(($statFunnelKandidat['tahap_kontrak'] / $totFun) * 100);
                $pSel = round(($statFunnelKandidat['tahap_selesai'] / $totFun) * 100);
            @endphp
            <div class="dash-segmented-bar">
                <div class="seg" style="width: {{ $pAwal }}%; background: #64748B;" title="Awal: {{ $pAwal }}%"></div>
                <div class="seg" style="width: {{ $pNego }}%; background: #F59E0B;" title="Nego: {{ $pNego }}%"></div>
                <div class="seg" style="width: {{ $pRev }}%; background: #3B82F6;" title="Review: {{ $pRev }}%"></div>
                <div class="seg" style="width: {{ $pKon }}%; background: #10B981;" title="Kontrak: {{ $pKon }}%"></div>
                <div class="seg" style="width: {{ $pSel }}%; background: #059669;" title="Selesai: {{ $pSel }}%"></div>
            </div>
            <div style="font-size: 12px; color: var(--text-secondary);">
                {{ $statFunnelKandidat['tahap_kontrak'] }} Lolos Kontrak · {{ $statFunnelKandidat['tahap_selesai'] }} Selesai
            </div>
        </a>
        <span class="info-hint"><i class="ti ti-info-circle"></i></span>
        <div class="dash-hover-popover">
            <div style="font-weight: 700; font-size: 12px; margin-bottom: 6px; color: var(--text-primary);">Distribusi Tahapan Kandidat</div>
            <div class="popover-row"><span>Tahap Awal (Diundang/Diajukan)</span><strong>{{ $statFunnelKandidat['tahap_awal'] }}</strong></div>
            <div class="popover-row"><span>Tahap Negosiasi Honor (Nego)</span><strong>{{ $statFunnelKandidat['tahap_nego'] }}</strong></div>
            <div class="popover-row"><span>Tahap Review Client (Deal/Ajukan)</span><strong>{{ $statFunnelKandidat['tahap_review'] }}</strong></div>
            <div class="popover-row"><span>Lolos & Kontrak Aktif</span><strong>{{ $statFunnelKandidat['tahap_kontrak'] }}</strong></div>
            <div class="popover-row"><span>Selesai Produksi</span><strong>{{ $statFunnelKandidat['tahap_selesai'] }}</strong></div>
        </div>
    </div>
</div>

<div class="sa-command-grid">
    {{-- Kolom Kiri Utama: Panel Tindakan Prioritas --}}
    <div style="display: flex; flex-direction: column; gap: 16px; min-width: 0;">
        <div class="card sa-card" style="border-top: 4px solid var(--warning, #D97706); margin: 0;">
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
                <a href="{{ route($tujuanStaf) }}" class="sa-lihat-semua">Lihat semua <i class="ti ti-arrow-right"></i></a>
            @endif
        </div>
    </div>

    {{-- Kolom Kanan Pendamping: Panel Kalender & Jadwal Sticky --}}
    <div class="sa-sticky-panel">
        <div class="card sa-card" style="margin: 0;">
            <div class="card-title"><i class="ti ti-calendar-event"></i> Jadwal &amp; Agenda Shooting</div>
            <x-navigasi-timeline :dari="$dariJadwal" :sampai="$sampaiJadwal" :skala="$skala" :action="route('super-admin.dashboard')" />
            <x-agenda :events="$agendaEvents" />
            <a href="{{ route('admin.projects.index') }}" class="sa-lihat-semua">Lihat semua <i class="ti ti-arrow-right"></i></a>
        </div>
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
