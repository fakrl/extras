@extends('layouts.app')

@section('title', 'Keuangan')

@section('content')
<div class="card-header-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
    <div>
        <div style="font-size: 18px; font-weight: 700;">Keuangan</div>
        <p style="font-size: 13px; color: var(--text-secondary); margin: 2px 0 0;">
            Pusat data keuangan operasional casting JBTB: margin proyek, honor staf, honor extras, dan invoice client.
        </p>
    </div>
    @if ($marginBulanIni->ada_data)
        <div style="text-align: right; background: var(--bg-card); border: 1px solid var(--border-color); padding: 8px 14px; border-radius: 8px;">
            <div style="font-size: var(--fs-xs); color: var(--text-muted); text-transform: uppercase;">Margin Selesai Bulan Ini</div>
            <div style="font-size: 16px; font-weight: 700; color: var(--accent-strong);">
                Rp {{ number_format($marginBulanIni->margin, 0, ',', '.') }}
            </div>
        </div>
    @endif
</div>

{{-- Sub-nav / Tabs --}}
<div style="display: flex; gap: 8px; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px; flex-wrap: wrap;">
    <a href="{{ request()->fullUrlWithQuery(['tab' => 'margin']) }}"
       class="btn btn-sm {{ ($tab ?? 'margin') === 'margin' ? 'btn-brand' : '' }}" style="border-radius: 6px;">
        <i class="ti ti-chart-arrows-vertical"></i> Margin Proyek
    </a>
    <a href="{{ request()->fullUrlWithQuery(['tab' => 'staf']) }}"
       class="btn btn-sm {{ ($tab ?? '') === 'staf' ? 'btn-brand' : '' }}" style="border-radius: 6px;">
        <i class="ti ti-users"></i> Honor Staf
    </a>
    <a href="{{ request()->fullUrlWithQuery(['tab' => 'extras']) }}"
       class="btn btn-sm {{ ($tab ?? '') === 'extras' ? 'btn-brand' : '' }}" style="border-radius: 6px;">
        <i class="ti ti-user-check"></i> Honor Extras
    </a>
    {{-- ditampilkan lagi setelah D5 --}}
</div>

<div style="position: relative; margin-bottom: 16px;">
    <input type="text" id="search-keuangan" placeholder="Cari di tabel (nama proyek, staf, extras, invoice)..."
           style="width: 100%; max-width: 400px; padding: 8px 14px 8px 36px; border: 1px solid var(--border-color); border-radius: 8px; font-size: var(--fs-md); background: var(--bg-card); color: var(--text-primary); margin-bottom: 0;">
    <i class="ti ti-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 15px;"></i>
</div>

@if (($tab ?? 'margin') === 'margin')
    {{-- TAB 1: MARGIN PROYEK --}}
    <div class="card">
        <div style="padding: 14px 16px; border-bottom: 1px solid var(--border-color); font-weight: 600;">
            Ringkasan Margin per Proyek
        </div>
        <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Proyek</th>
                    <th>Fee Client</th>
                    <th>Payout Extras</th>
                    <th>Margin</th>
                    <th>Margin %</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($projects as $i => $row)
                    <tr>
                        <td style="font-weight: 600;">
                            <button type="button" onclick="toggleExtras({{ $i }})" style="background:none; border:none; padding:0; font:inherit; font-weight:600; color:inherit; text-decoration:underline; cursor:pointer;">{{ $row->project->nama_produksi }}</button>
                        </td>
                        <td>Rp {{ number_format($row->total_fee_client, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($row->total_payout, 0, ',', '.') }}</td>
                        <td>
                            <strong style="color: {{ $row->margin >= 0 ? 'var(--accent-strong)' : 'var(--danger)' }};">
                                Rp {{ number_format($row->margin, 0, ',', '.') }}
                            </strong>
                            @if ($row->belum_terklasifikasi)
                                <span class="badge badge-pending" title="Termasuk data belum terklasifikasi">Perlu Cek</span>
                            @endif
                        </td>
                        <td>{{ number_format($row->margin_persen, 1) }}%</td>
                        <td style="white-space: nowrap; font-size: 12px; color: var(--accent);">
                            <button type="button" onclick="toggleExtras({{ $i }})" style="background:none; border:none; padding:0; font:inherit; color:var(--accent); cursor:pointer;">Detail Extras &rarr;</button>
                        </td>
                    </tr>
                    @if ($row->breakdown->count() > 1 || $row->belum_terklasifikasi)
                        @foreach ($row->breakdown as $kelas)
                            <tr style="color: var(--text-secondary); font-size: 12.5px;">
                                <td style="padding-left: 24px;">&mdash; {{ $kelas->kelas->nama_kelas }} ({{ $kelas->jumlah_aplikasi }} orang)</td>
                                <td>Rp {{ number_format($kelas->total_fee_client, 0, ',', '.') }}</td>
                                <td>
                                    Rp {{ number_format($kelas->total_payout, 0, ',', '.') }}
                                    @if ($kelas->ada_fee_null)
                                        <span class="badge badge-pending" title="{{ $kelas->jumlah_fee_null }} kandidat belum ada fee deal">Fee belum diset</span>
                                    @endif
                                </td>
                                <td>Rp {{ number_format($kelas->margin, 0, ',', '.') }}</td>
                                <td>&mdash;</td>
                                <td></td>
                            </tr>
                        @endforeach
                        @if ($row->belum_terklasifikasi)
                            <tr style="color: var(--warning); background: rgba(240,185,11,0.08); font-size: 12.5px;">
                                <td style="padding-left: 24px;">
                                    &mdash; Belum terklasifikasi ({{ $row->belum_terklasifikasi->jumlah_aplikasi }} orang)
                                    <span class="badge badge-pending">Data belum lengkap</span>
                                </td>
                                <td>Rp 0</td>
                                <td>Rp {{ number_format($row->belum_terklasifikasi->total_payout, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format(-$row->belum_terklasifikasi->total_payout, 0, ',', '.') }}</td>
                                <td>&mdash;</td>
                                <td></td>
                            </tr>
                        @endif
                    @endif
                    {{-- Drill-down extras (hidden) --}}
                    <tr id="extras-{{ $i }}" style="display:none;">
                        <td colspan="6" style="padding: 0; background: var(--bg-page);">
                            <div style="padding: 12px 24px;">
                                <div class="table-container">
                                <table style="width: 100%; font-size: 12.5px;">
                                    <thead>
                                        <tr style="color: var(--text-muted);">
                                            <th style="text-align: left; padding: 4px 8px;">Alias / Nama</th>
                                            <th style="text-align: left; padding: 4px 8px;">Fee Final</th>
                                            <th style="text-align: left; padding: 4px 8px;">Status Partisipasi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($row->project->applications as $app)
                                            <tr>
                                                <td style="padding: 4px 8px;">{{ $app->extras?->user?->username ?? $app->extras?->user?->name ?? '-' }}</td>
                                                <td style="padding: 4px 8px;">
                                                    @if ($app->fee_final !== null)
                                                        Rp {{ number_format($app->fee_final, 0, ',', '.') }}
                                                    @else
                                                        <span style="color: var(--warning);">Belum diset</span>
                                                    @endif
                                                </td>
                                                <td style="padding: 4px 8px;"><x-status-badge :model="$app" /></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center; color: var(--text-muted); padding: 24px;">Belum ada data margin proyek.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

@elseif ($tab === 'staf')
    {{-- TAB 2: HONOR STAF --}}
    <div class="card">
        <div style="padding: 14px 16px; border-bottom: 1px solid var(--border-color); font-weight: 600;">
            Daftar Honor Staf Admin & Korlap
        </div>
        <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Staf</th>
                    <th>Role</th>
                    <th>Proyek</th>
                    <th>Honor Pokok</th>
                    <th>Addon / Reimburse</th>
                    <th>Total Honor</th>
                    <th>Status Bayar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($staffPayrolls as $payroll)
                    @php
                        $user = $payroll->assignment?->user;
                        $project = $payroll->assignment?->castingProject;
                        $addonsSum = $payroll->addons->sum('nominal');
                    @endphp
                    <tr>
                        <td style="font-weight: 600;">{{ $user?->name ?? '-' }}</td>
                        <td>@if($user)<x-status-badge :model="$user" />@else - @endif</td>
                        <td>{{ $project?->nama_produksi ?? '-' }}</td>
                        <td>Rp {{ number_format($payroll->nominal_pokok, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($addonsSum, 0, ',', '.') }}</td>
                        <td style="font-weight: 700;">Rp {{ number_format($payroll->nominalTotal(), 0, ',', '.') }}</td>
                        <td>
                            @if ($payroll->isDibayar())
                                <span class="badge badge-aktif">Sudah Dibayar</span>
                                <div style="font-size: var(--fs-xs); color: var(--text-muted);">{{ $payroll->dibayar_at?->format('d/m/Y H:i') }}</div>
                            @else
                                <span class="badge badge-pending">Belum Dibayar</span>
                            @endif
                        </td>
                        <td>
                            @if (! $payroll->isDibayar())
                                <form method="POST" action="{{ route('admin.payrolls.tandai-dibayar', $payroll) }}" style="display: inline;" onsubmit="return confirm('Tandai honor staf ini sudah dibayarkan?')">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm btn-brand">Tandai Dibayar</button>
                                </form>
                            @else
                                <span style="font-size: 12px; color: var(--text-muted);">&check; Lunas</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="text-align:center; color: var(--text-muted); padding: 24px;">Belum ada data honor staf.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

@elseif ($tab === 'extras')
    {{-- TAB 3: HONOR EXTRAS --}}
    <div class="card">
        <div style="padding: 14px 16px; border-bottom: 1px solid var(--border-color); font-weight: 600;">
            Status Pembayaran Honor Extras
        </div>
        <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Extras</th>
                    <th>Proyek</th>
                    <th>Nominal Pokok</th>
                    <th>Addon</th>
                    <th>Total</th>
                    <th>Status Pembayaran</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($extrasPayments as $pay)
                    @php
                        $exUser = $pay->projectApplication?->extras?->user;
                        $proj = $pay->projectApplication?->castingProject;
                    @endphp
                    <tr>
                        <td style="font-weight: 600;">{{ $exUser?->username ?? $exUser?->name ?? '-' }}</td>
                        <td>{{ $proj?->nama_produksi ?? '-' }}</td>
                        <td>Rp {{ number_format($pay->nominal_pokok, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($pay->addons->sum('nominal'), 0, ',', '.') }}</td>
                        <td style="font-weight: 700;">Rp {{ number_format($pay->nominalTotal(), 0, ',', '.') }}</td>
                        <td><x-status-badge :model="$pay" /></td>
                        <td>
                            @if ($pay->projectApplication)
                                <a href="{{ route('payments.show', $pay->projectApplication) }}" class="btn btn-sm">Lihat Pembayaran</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align:center; color: var(--text-muted); padding: 24px;">Belum ada data pembayaran extras.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

@endif
{{-- Tab Invoice Client disembunyikan: view baca kolom yang belum ada
     (nomor_invoice, total_tagihan, status_pembayaran). ditampilkan lagi setelah D5 --}}

@endsection

@push('scripts')
<script>
function toggleExtras(i) {
    var row = document.getElementById('extras-' + i);
    if (row) {
        row.style.display = row.style.display === 'none' ? '' : 'none';
    }
}

(function () {
    var searchInput = document.getElementById('search-keuangan');
    if (!searchInput) return;

    searchInput.addEventListener('input', function () {
        var q = this.value.toLowerCase().trim();
        var tables = document.querySelectorAll('table');
        tables.forEach(function (table) {
            var rows = table.querySelectorAll('tbody tr');
            rows.forEach(function (row) {
                var text = row.textContent.toLowerCase();
                row.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        });
    });
})();
</script>
@endpush
