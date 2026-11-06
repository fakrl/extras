@extends('layouts.app')

@section('title', auth()->user()->bisaSebagaiAdmin() && auth()->user()->modeSa() !== 'korlap' ? 'Dashboard Admin' : 'Dashboard Korlap')

@include('super-admin.monitoring._gaya')

@section('content')
@php $tampilAdmin = auth()->user()->bisaSebagaiAdmin() && auth()->user()->modeSa() !== 'korlap'; @endphp
@if ($tampilAdmin)
    <div style="margin-bottom: 20px;">
        <span class="label-editorial-sm">Studio Operasional Proyek</span>
        <h1 class="font-editorial" style="font-size: 22px; font-weight: 700; margin: 0 0 4px;">Dashboard Operasional Admin · {{ auth()->user()->name }}</h1>
        <div style="font-size: 13.5px; color: var(--text-secondary);">Pantau jadwal syuting, tindak lanjuti kasus mendesak, dan koordinasikan lineup talenta.</div>
    </div>
    @php $tindakan = array_filter($ringkasan, fn ($r) => $r['jumlah'] > 0); $jumlahTindakan = $urgentProjects->count() + $pembayaranSengketa + array_sum(array_column($tindakan, 'jumlah')); @endphp
    <div class="dash-tiga">
        <div class="card">
            <div class="card-title" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                <span>Jadwal Shooting</span>
            </div>
            <x-navigasi-timeline :dari="$dari" :sampai="$sampai" :skala="$skala" :action="route('admin.dashboard')" />
            <x-agenda :events="$agendaEvents" />
        </div>

        <div class="card dash-perlu {{ $jumlahTindakan ? '' : 'is-aman' }}">
            <div class="card-title">
                <i class="ti ti-clipboard-list"></i> Perlu Tindakan
                @if ($jumlahTindakan)
                    <span class="badge badge-netral" style="margin-left: 8px;">{{ $jumlahTindakan }}</span>
                @endif
            </div>

            @if ($urgentProjects->isNotEmpty())
                <p class="dash-sub" style="margin: 0 0 4px;">
                    <strong style="color: var(--danger);"><i class="ti ti-alert-triangle"></i> Ada {{ $urgentProjects->count() }} Proyek Berstatus Urgent / H-3!</strong>
                    Tanggal shooting sudah sangat dekat namun kuota kandidat belum terpenuhi. Segera bagikan link pendaftaran ke grup WA atau review lineup.
                </p>
                @foreach ($urgentProjects as $p)
                    <a href="{{ route('admin.projects.show', $p) }}" class="dash-row">
                        <div>
                            <span class="badge badge-urgent">Urgent</span>
                            <strong>{{ $p->nama_produksi }}</strong>
                            <div class="dash-sub">{{ collect([$p->client ? $p->namaClient() : null, $p->rentangShooting()])->filter(fn ($x) => $x && $x !== "-")->implode(" • ") }}</div>
                        </div>
                        <span class="dash-sub">Buka <i class="ti ti-arrow-right"></i></span>
                    </a>
                @endforeach
                <a href="{{ route('admin.projects.index', ['urgent' => 1]) }}" class="dash-row" style="color: var(--accent); font-weight: 600;">Lihat Proyek Urgent <i class="ti ti-arrow-right"></i></a>
            @endif

            @if ($pembayaranSengketa > 0)
                <a href="{{ $linkSengketa }}" class="dash-row">
                    <div>
                        <span class="badge badge-pending">Pembayaran Bermasalah</span>
                        <strong>{{ $pembayaranSengketa }} kasus</strong>
                        <div class="dash-sub">Extras menyengketakan pembayaran</div>
                    </div>
                    <span class="dash-sub">Tinjau Pembayaran <i class="ti ti-arrow-right"></i></span>
                </a>
            @endif

            @foreach ($tindakan as $r)
                <a href="{{ $r['url'] }}" class="dash-row">
                    <div><strong>{{ $r['jumlah'] }}</strong> {{ $r['label'] }}</div>
                    <span class="dash-sub">Buka <i class="ti ti-arrow-right"></i></span>
                </a>
            @endforeach

            @unless ($jumlahTindakan)
                <div class="dash-aman" role="status"><i class="ti ti-circle-check"></i> Semua aman · tidak ada yang perlu ditindak.</div>
            @endunless
        </div>

        <div class="card">
            <div class="card-title">Ringkasan</div>
            <div class="dash-metrik" style="margin-bottom: 14px;">
                <div class="dash-stat-card metric-card" tabindex="0" style="padding: 10px 12px; cursor: pointer;">
                    <div>
                        <div class="metric-label" style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Proyek Aktif</span>
                            <i class="ti ti-info-circle" style="font-size: 13px; color: var(--text-muted);" title="Klik untuk rincian / kunci pop-up"></i>
                        </div>
                        @php
                            $totAgency = max(1, $breakdownAdmin['proyek_agency']);
                            $pctPic = min(100, round(($breakdownAdmin['proyek_pic'] / $totAgency) * 100));
                            $ringPicLen = round(($pctPic / 100) * 188.5, 1);
                        @endphp
                        <div class="dash-viz-body" style="margin-top: 6px;">
                            <div class="viz-ring-wrap" style="width: 58px; height: 58px;" title="PIC Saya: {{ $breakdownAdmin['proyek_pic'] }} dari {{ $breakdownAdmin['proyek_agency'] }} proyek">
                                <svg class="viz-ring-svg" viewBox="0 0 76 76" style="width: 58px; height: 58px;" aria-hidden="true">
                                    <circle cx="38" cy="38" r="30" stroke="var(--surface-2, rgba(0,0,0,0.06))" stroke-width="7" fill="none" />
                                    <circle cx="38" cy="38" r="30" stroke="#15803D" stroke-width="7" stroke-dasharray="{{ $ringPicLen }} 188.5" stroke-linecap="round" fill="none" />
                                </svg>
                                <div class="viz-center-label">
                                    <span class="viz-center-num" style="font-size: 14px;">{{ $proyekAktif }}</span>
                                    <span class="viz-center-sub" style="font-size: 8px;">Aktif</span>
                                </div>
                            </div>
                            <div class="viz-legend">
                                <div class="viz-legend-row" style="font-size: 11px;">
                                    <span class="viz-legend-label"><span class="viz-dot" style="background: #15803D;"></span>PIC Saya</span>
                                    <strong class="viz-legend-val">{{ $breakdownAdmin['proyek_pic'] }}</strong>
                                </div>
                                <div class="viz-legend-row" style="font-size: 11px;">
                                    <span class="viz-legend-label"><span class="viz-dot" style="background: #64748B;"></span>Agency</span>
                                    <strong class="viz-legend-val">{{ $breakdownAdmin['proyek_agency'] }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="dash-hover-popover">
                        <div class="dash-popover-header">
                            <span class="dash-popover-title">Distribusi Proyek Aktif</span>
                            <button type="button" class="dash-popover-close" aria-label="Tutup"><i class="ti ti-x"></i></button>
                        </div>
                        <div class="popover-row"><span>Proyek Saya (PIC)</span><strong>{{ $breakdownAdmin['proyek_pic'] }}</strong></div>
                        <div class="popover-row"><span>Total Proyek Agency</span><strong>{{ $breakdownAdmin['proyek_agency'] }}</strong></div>
                        <div class="popover-row"><span>Buka Lowongan</span><strong>{{ $breakdownAdmin['proyek_lowongan'] }}</strong></div>
                        <div class="popover-row"><span>Proses Seleksi</span><strong>{{ $breakdownAdmin['proyek_seleksi'] }}</strong></div>
                        <div class="popover-row"><span>Shooting Berjalan</span><strong>{{ $breakdownAdmin['proyek_shooting'] }}</strong></div>
                        <div style="margin-top: 8px; padding-top: 6px; border-top: 1px dashed var(--border-color);">
                            <a href="{{ route('admin.projects.index', ['status' => 'dibuka']) }}" class="btn btn-sm btn-tonal" style="width: 100%; justify-content: center; font-size: 11.5px; gap: 4px;">Buka Proyek Aktif <i class="ti ti-arrow-right"></i></a>
                        </div>
                    </div>
                </div>

                <div class="dash-stat-card metric-card" tabindex="0" style="padding: 10px 12px; cursor: pointer;">
                    <div>
                        <div class="metric-label" style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Total Pendaftar</span>
                            <i class="ti ti-info-circle" style="font-size: 13px; color: var(--text-muted);" title="Klik untuk rincian / kunci pop-up"></i>
                        </div>
                        @php
                            $totPendaftarSum = max(1, $totalPendaftar);
                            $lenReview = round(($breakdownAdmin['pendaftar_review'] / $totPendaftarSum) * 188.5, 1);
                            $lenNego = round(($breakdownAdmin['pendaftar_nego'] / $totPendaftarSum) * 188.5, 1);
                            $lenClient = round(($breakdownAdmin['pendaftar_client'] / $totPendaftarSum) * 188.5, 1);
                            $lenKontrak = max(0, round(188.5 - $lenReview - $lenNego - $lenClient, 1));
                        @endphp
                        <div class="dash-viz-body" style="margin-top: 6px;">
                            <div class="viz-donut-wrap" style="width: 58px; height: 58px;">
                                <svg class="viz-donut-svg" viewBox="0 0 76 76" style="width: 58px; height: 58px;" aria-hidden="true">
                                    <circle cx="38" cy="38" r="30" stroke="var(--surface-2, rgba(0,0,0,0.06))" stroke-width="7" fill="none" />
                                    <circle cx="38" cy="38" r="30" stroke="#64748B" stroke-width="7" stroke-dasharray="{{ $lenReview }} 188.5" stroke-dashoffset="0" fill="none" />
                                    <circle cx="38" cy="38" r="30" stroke="#D97706" stroke-width="7" stroke-dasharray="{{ $lenNego }} 188.5" stroke-dashoffset="{{ -$lenReview }}" fill="none" />
                                    <circle cx="38" cy="38" r="30" stroke="#0284C7" stroke-width="7" stroke-dasharray="{{ $lenClient }} 188.5" stroke-dashoffset="{{ -($lenReview + $lenNego) }}" fill="none" />
                                    <circle cx="38" cy="38" r="30" stroke="#15803D" stroke-width="7" stroke-dasharray="{{ $lenKontrak }} 188.5" stroke-dashoffset="{{ -($lenReview + $lenNego + $lenClient) }}" fill="none" />
                                </svg>
                                <div class="viz-center-label">
                                    <span class="viz-center-num" style="font-size: 14px;">{{ $totalPendaftar }}</span>
                                    <span class="viz-center-sub" style="font-size: 8px;">Talent</span>
                                </div>
                            </div>
                            <div class="viz-legend">
                                <div class="viz-legend-row" style="font-size: 11px;">
                                    <span class="viz-legend-label"><span class="viz-dot" style="background: #64748B;"></span>Review</span>
                                    <strong class="viz-legend-val">{{ $breakdownAdmin['pendaftar_review'] }}</strong>
                                </div>
                                <div class="viz-legend-row" style="font-size: 11px;">
                                    <span class="viz-legend-label"><span class="viz-dot" style="background: #D97706;"></span>Nego</span>
                                    <strong class="viz-legend-val">{{ $breakdownAdmin['pendaftar_nego'] }}</strong>
                                </div>
                                <div class="viz-legend-row" style="font-size: 11px;">
                                    <span class="viz-legend-label"><span class="viz-dot" style="background: #15803D;"></span>Kontrak</span>
                                    <strong class="viz-legend-val">{{ $breakdownAdmin['pendaftar_kontrak'] }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="dash-hover-popover">
                        <div class="dash-popover-header">
                            <span class="dash-popover-title">Status Pendaftar</span>
                            <button type="button" class="dash-popover-close" aria-label="Tutup"><i class="ti ti-x"></i></button>
                        </div>
                        <div class="popover-row"><span>Menunggu Review</span><strong>{{ $breakdownAdmin['pendaftar_review'] }}</strong></div>
                        <div class="popover-row"><span>Sedang Nego Fee</span><strong>{{ $breakdownAdmin['pendaftar_nego'] }}</strong></div>
                        <div class="popover-row"><span>Pilihan Client</span><strong>{{ $breakdownAdmin['pendaftar_client'] }}</strong></div>
                        <div class="popover-row"><span>Belum TTD Kontrak</span><strong>{{ $breakdownAdmin['pendaftar_kontrak'] }}</strong></div>
                        <div style="margin-top: 8px; padding-top: 6px; border-top: 1px dashed var(--border-color);">
                            <a href="{{ route('admin.projects.index') }}" class="btn btn-sm btn-tonal" style="width: 100%; justify-content: center; font-size: 11.5px; gap: 4px;">Kelola Pelamar <i class="ti ti-arrow-right"></i></a>
                        </div>
                    </div>
                </div>

                <a href="{{ route('admin.projects.index', ['peserta' => 'nego_fee']) }}" class="metric-card" style="grid-column: span 2;">
                    <div class="metric-label">Sedang nego</div>
                    <div class="metric-value">{{ $perluDinego }}</div>
                </a>
            </div>

            @if ($proyekBottleneck->isNotEmpty())
                <div style="margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px dashed var(--border-color);">
                    <div style="font-size: 12.5px; font-weight: 700; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; color: var(--text-primary);">
                        <i class="ti ti-users-group"></i> Peringatan Kuota Proyek
                    </div>
                    @foreach ($proyekBottleneck->take(3) as $pb)
                        <div style="background: var(--surface-1, rgba(0,0,0,0.02)); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 8px 10px; margin-bottom: 8px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; margin-bottom: 4px;">
                                <strong style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 160px;">{{ $pb->nama_produksi }}</strong>
                                <span style="font-size: 11px; font-weight: 600;">{{ $pb->terisi_kuota }}/{{ $pb->target_kuota }} ({{ $pb->persen_kuota }}%)</span>
                            </div>
                            <div class="dash-segmented-bar" style="height: 6px; margin: 4px 0 6px;">
                                <div class="seg" style="width: {{ $pb->persen_kuota }}%; background: {{ $pb->persen_kuota < 50 ? '#DC2626' : '#D97706' }};"></div>
                                <div class="seg" style="width: {{ 100 - $pb->persen_kuota }}%; background: var(--surface-2, rgba(0,0,0,0.06));"></div>
                            </div>
                            <div style="display: flex; justify-content: flex-end;">
                                <a href="{{ route('admin.projects.applicants', $pb) }}" class="btn btn-sm btn-tonal" style="font-size: 11px; padding: 2px 8px;">
                                    <i class="ti ti-users-group"></i> Penuhi Lineup <i class="ti ti-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <a href="{{ route('admin.projects.index') }}" class="dash-row"><span><i class="ti ti-folder"></i> Kelola Proyek</span><span class="dash-sub"><i class="ti ti-arrow-right"></i></span></a>
            <a href="{{ route('admin.akun.extras') }}" class="dash-row"><span><i class="ti ti-users"></i> Kelola Akun Extras &amp; Rekap</span><span class="dash-sub"><i class="ti ti-arrow-right"></i></span></a>
            <a href="{{ route('admin.attendance.index') }}" class="dash-row"><span><i class="ti ti-camera"></i> Absensi Lapangan</span><span class="dash-sub"><i class="ti ti-arrow-right"></i></span></a>
            <a href="{{ route('admin.work-history') }}" class="dash-row"><span><i class="ti ti-wallet"></i> Honor & Reimburse Saya</span><span class="dash-sub"><i class="ti ti-arrow-right"></i></span></a>
        </div>
    </div>

    <div class="dashboard-grid-2col is-wide-narrow">
        <div class="card" style="min-width: 0;">
            <div class="card-title">Tahapan Partisipasi Kandidat</div>
            @include('admin.partials.tahapan')
        </div>
        <div class="card">
            <div class="card-title">Status Pembayaran Extras</div>
            <div class="chart-box"><canvas id="chartPembayaran"></canvas></div>
        </div>
    </div>
@elseif (auth()->user()->bisaSebagaiKorlap())
    <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px;">
        <a href="{{ route('admin.attendance.index') }}" class="btn btn-brand"><i class="ti ti-camera"></i> Absensi Lapangan</a>
        <a href="{{ route('admin.work-history') }}" class="btn">Honor & Reimburse Saya</a>
    </div>
    @if ($punyaTugas)
        @include('partials.korlap-ringkasan', ['masuk' => false])
    @else
        <div class="card"><div class="dash-sub" role="status">Belum ada proyek yang ditugaskan kepadamu.</div></div>
    @endif
@else
    <div class="alert-info">
        Akses kamu sebagai {{ auth()->user()->role }} terbatas ke pencatatan penugasan & riwayat kerja.
    </div>
    <a href="{{ route('admin.work-history') }}" class="btn">Honor & Reimburse Saya</a>
@endif
@endsection

@if (auth()->user()->bisaSebagaiAdmin() && auth()->user()->modeSa() !== 'korlap')
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    var gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
    var textColor = isDark ? '#9db3a2' : '#435449';

    new Chart(document.getElementById('chartPembayaran'), {
        type: 'doughnut',
        data: {
            labels: @json($chartStatusPembayaran['labels']),
            datasets: [{
                data: @json($chartStatusPembayaran['data']),
                backgroundColor: ['#374151', '#eab308', '#22c55e', '#9ca3af'],
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
@endif
