@extends('layouts.app')

@section('title', $castingProject->nama_produksi)

@section('content')
@php
    $p = $castingProject;
    $rp = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
    $tahap = $p->tahap();
    $korlap = $p->adminAssignments->filter(fn ($a) => $a->user?->isKorlap());
    $stafLain = $p->adminAssignments->reject(fn ($a) => $a->user?->isKorlap());
@endphp

<div class="card-header-row" style="flex-wrap: wrap; gap: 10px;">
    <div>
        <div style="font-size: var(--fs-xs); color: var(--text-muted);"><a href="{{ route('admin.projects.index') }}">&larr; Proyek</a></div>
        <div style="font-size: 18px; font-weight: 700;">
            {{ $p->nama_produksi }}
            <span class="kode-proyek">{{ $p->kode_proyek }}</span>
            @if ($tahap)
                <span class="badge {{ \App\Models\CastingProject::TAHAP_BADGES[$tahap] }}">{{ \App\Models\CastingProject::TAHAP[$tahap] }}</span>
            @endif
        </div>
        <div style="font-size: var(--fs-sm); color: var(--text-secondary);">
            {{ $p->client?->name ?? '-' }} · PIC {{ $p->admin?->name ?? '-' }} · Shooting {{ $p->rentangShooting() }}
        </div>
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="{{ route('admin.projects.edit', $p) }}" class="btn btn-sm">Edit</a>
        <a href="{{ route('admin.projects.applicants', $p) }}" class="btn btn-sm">Lineup</a>
        <a href="{{ route('invoices.show', $p) }}" class="btn btn-sm">Invoice</a>
    </div>
</div>

<div class="xfilter" role="tablist" aria-label="Bagian detail proyek">
    @foreach (['info' => 'Info', 'pendaftar' => 'Pendaftar', 'keuangan' => 'Keuangan', 'lampiran' => 'Lampiran'] as $key => $label)
        <a href="{{ route('admin.projects.show', [$p, 'tab' => $key === 'info' ? null : $key]) }}" role="tab"
           class="btn btn-sm {{ $tab === $key ? 'btn-brand' : '' }}" @if ($tab === $key) aria-selected="true" @endif>{{ $label }}</a>
    @endforeach
</div>

@if ($tab === 'info')
    <div class="detail-grid">
        <div class="card">
            <div class="card-title">Proyek</div>
            @foreach ([
                'Client (akun)' => $p->client ? $p->client->name.($p->client->username ? ' (@'.$p->client->username.')' : '') : '-',
                'Nama Client / PH' => $p->client?->nama_perusahaan ?: '-',
                'Admin PIC' => $p->admin?->name ?? '-',
                'Korlap' => $korlap->map(fn ($a) => $a->user->name)->implode(', ') ?: '-',
                'Staf lain' => $stafLain->map(fn ($a) => $a->user?->name)->filter()->implode(', ') ?: '-',
                'Deadline daftar' => $p->deadline?->translatedFormat('d M Y') ?? '-',
                'Kuota total' => $p->kuota.' orang',
                'Lowongan' => ucfirst($p->status),
                'Pengajuan' => ucfirst(str_replace('_', ' ', $p->client_request_status)),
                'Urgent' => $p->is_urgent ? 'Ya' : 'Tidak',
                'Fee' => $p->nego_terbuka ? 'Terbuka untuk nego fee' : 'Fee tetap',
            ] as $label => $value)
                <div class="entity-card-row">
                    <span class="entity-card-row-label">{{ $label }}</span>
                    <span class="entity-card-row-value">{{ $value }}</span>
                </div>
            @endforeach
            @foreach (['Grup koordinasi' => $p->link_grup] as $label => $link)
                @if ($link)
                    <div class="entity-card-row">
                        <span class="entity-card-row-label">{{ $label }}</span>
                        <span class="entity-card-row-value"><a href="{{ $link }}" target="_blank" rel="noopener">Buka link</a></span>
                    </div>
                @endif
            @endforeach
            @if ($p->brief_catatan)
                <div style="margin-top: 10px; font-size: var(--fs-sm);"><strong>Brief:</strong> {{ $p->brief_catatan }}</div>
            @endif
        </div>

        <div class="card">
            <div class="card-title">Jadwal Shooting</div>
            @forelse ($p->shootingDates as $d)
                <div class="entity-card-row">
                    <span class="entity-card-row-label">{{ $d->tanggal->translatedFormat('D, d M Y') }}</span>
                    <span class="entity-card-row-value">{{ implode(' · ', array_filter([$d->jam_mulai ? substr($d->jam_mulai, 0, 5).($d->jam_selesai ? '–'.substr($d->jam_selesai, 0, 5) : '') : null, $d->lokasi])) ?: '-' }}</span>
                </div>
            @empty
                <div style="color: var(--text-muted); font-size: var(--fs-sm);">Belum ada jadwal.</div>
            @endforelse
        </div>
    </div>

    @if ($p->bisaPortofolio() && auth()->user()->bisaSebagaiAdmin())
        <div class="card" style="margin-top: 14px;">
            <div class="card-title">Portofolio di beranda</div>
            <form method="POST" action="{{ route('admin.projects.portofolio', $p) }}">
                @csrf @method('PATCH')
                <label style="display: flex; gap: 8px; align-items: center; font-weight: 600;">
                    <input type="checkbox" name="tampil_portofolio" value="1" style="width: auto; min-height: auto; margin: 0;" @checked($p->tampil_portofolio)>
                    Tampilkan proyek ini di "Pernah dikerjakan" beranda
                </label>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0 12px; margin-top: 10px;">
                    <div>
                        <label for="portofolio_judul">Judul tampil</label>
                        <input type="text" id="portofolio_judul" name="portofolio_judul" maxlength="150" value="{{ old('portofolio_judul', $p->portofolio_judul) }}" placeholder="{{ $p->nama_produksi }}">
                    </div>
                    <div>
                        <label for="portofolio_jenis">Jenis</label>
                        <input type="text" id="portofolio_jenis" name="portofolio_jenis" maxlength="80" list="jenis-produksi" value="{{ old('portofolio_jenis', $p->portofolio_jenis) }}" placeholder="Film layar lebar">
                        <datalist id="jenis-produksi"><option value="Film layar lebar"><option value="Series"><option value="Iklan TV"><option value="FTV"><option value="Video klip"></datalist>
                    </div>
                    <div>
                        <label for="portofolio_tahun">Tahun</label>
                        <input type="number" id="portofolio_tahun" name="portofolio_tahun" min="2000" max="{{ now()->year + 1 }}" inputmode="numeric" value="{{ old('portofolio_tahun', $p->portofolio_tahun ?? $p->shootingDates->max('tanggal')?->year) }}">
                    </div>
                </div>
                @foreach (['portofolio_judul', 'portofolio_jenis', 'portofolio_tahun'] as $f)
                    @error($f)<span class="field-error">{{ $message }}</span>@enderror
                @endforeach
                <label style="display: flex; gap: 8px; align-items: center;">
                    <input type="checkbox" name="tampilkan_nama_client" value="1" style="width: auto; min-height: auto; margin: 0;" @checked($p->tampilkan_nama_client)>
                    Tampilkan nama client ({{ $p->namaClient() }})
                </label>
                <p style="font-size: var(--fs-xs); color: var(--text-muted); margin: 4px 0 10px;">Nama client default disembunyikan. Centang hanya kalau client sudah setuju.</p>
                <button type="submit" class="btn btn-sm btn-brand">Simpan portofolio</button>
            </form>
        </div>
    @endif

    <div class="card" style="margin-top: 14px;">
        <div class="card-title">Peran</div>
        <div class="table-container">
            <table>
                <thead><tr><th>Peran</th><th>Kuota</th><th>Budget Client</th><th>Callingan</th><th>Tag dicari</th></tr></thead>
                <tbody>
                    @forelse ($p->classes as $k)
                        <tr>
                            <td><strong>{{ $k->nama_kelas }}</strong>
                                @if ($k->kriteria)<div style="font-size: var(--fs-xs); color: var(--text-secondary);">{{ $k->kriteria }}</div>@endif</td>
                            <td>{{ $k->kuota_kelas }}</td>
                            <td>{{ $rp($k->budget_client) }}</td>
                            <td>{{ $k->jam_callingan ? substr($k->jam_callingan, 0, 5) : '-' }}</td>
                            <td>
                                <div class="xcard-tags">
                                    @forelse ($k->categories as $tag)
                                        <span class="xtag">#{{ $tag->nama }}</span>
                                    @empty
                                        <span style="color: var(--text-muted);">-</span>
                                    @endforelse
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="text-align: center; color: var(--text-muted);">Belum ada peran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@elseif ($tab === 'pendaftar')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
        <div style="font-size: var(--fs-sm); color: var(--text-secondary);">{{ $pendaftar->flatten()->count() }} pendaftar / kuota {{ $p->kuota }}</div>
        <a href="{{ route('admin.projects.applicants', $p) }}" class="btn btn-sm btn-brand">Buka Lineup lengkap &rarr;</a>
    </div>
    @foreach (\App\Models\ProjectApplication::LABELS as $status => $label)
        @continue(! $pendaftar->has($status))
        <div style="font-weight: 600; margin: 14px 0 8px;">{{ $label }} <span class="badge badge-netral">{{ $pendaftar[$status]->count() }}</span></div>
        <div class="xgrid">
            @foreach ($pendaftar[$status] as $app)
                @include('partials.extras-card', [
                    'profile' => $app->extras,
                    'aplikasi' => $app,
                    'sub' => $app->extras->user->name ?? null,
                    'lihat' => ['href' => route('admin.extras.profil', $app->extras->user_id), 'data-profil-modal' => true, 'data-aksi-url' => $diLineup = route('admin.projects.applicants', [$p, 'status' => $status]).'#app-'.$app->id, 'data-aksi-label' => 'Di Lineup'],
                    'aksi' => ['label' => 'Di Lineup', 'href' => $diLineup],
                ])
            @endforeach
        </div>
    @endforeach
    @if ($pendaftar->isEmpty())
        <div class="card" style="text-align: center; color: var(--text-muted); padding: 30px 0;">Belum ada pendaftar.</div>
    @endif

@elseif ($tab === 'lampiran')
    @include('partials.project-attachments', ['project' => $p])

@else
    @php
        $inv = $p->invoices->first();
        $lunas = $inv?->isLunas();
        $nominalInv = (int) ($inv?->nominal ?? $usulanInvoice);
    @endphp
    <div class="card cf-seksi">
        <div class="card-title">Invoice Client</div>
        <div class="table-container">
            <table>
                <thead><tr><th>Invoice</th><th>Nominal</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    <tr>
                        <td>
                            <a href="{{ route('invoices.show', $p) }}">Invoice {{ $p->nama_produksi }}</a>
                            @if ($inv?->pdf_path)
                                · <a href="{{ route('invoices.download-pdf', $p) }}">PDF</a>
                            @endif
                            @if (! $inv?->nominal && ! $lunas)
                                <div style="font-size: var(--fs-xs); color: var(--text-muted);">Nominal belum diisi · usulan dari rincian peran, bisa diedit</div>
                            @endif
                        </td>
                        <td>
                            @if ($lunas)
                                {{ $rp($inv->nominal) }}
                            @else
                                <form method="POST" action="{{ route('admin.projects.invoice-nominal', $p) }}" style="display: flex; gap: 6px; flex-wrap: wrap;">
                                    @csrf @method('PATCH')
                                    <input type="number" name="nominal" value="{{ old('nominal', $nominalInv) }}" min="0" step="1" required aria-label="Nominal invoice" style="width: 140px; margin: 0; min-height: 32px;">
                                    <button type="submit" class="btn btn-sm">Simpan Nominal</button>
                                </form>
                            @endif
                        </td>
                        <td>
                            @if ($lunas)
                                <span class="badge badge-aktif">Lunas</span>
                                <div style="font-size: var(--fs-xs); color: var(--text-muted);">{{ $inv->dibayar_at?->translatedFormat('d M Y H:i') }}</div>
                            @else
                                <span class="badge badge-pending">Belum</span>
                            @endif
                        </td>
                        <td>
                            @unless ($lunas)
                                <x-confirm-form :action="route('admin.projects.invoice-lunas', $p)" method="PATCH" message="Tandai invoice ini lunas? Nominal tersimpan dan tidak bisa dibatalkan dari sini.">
                                    <input type="hidden" name="nominal" value="{{ $nominalInv }}">
                                    <button type="submit" class="btn btn-sm btn-brand">Tandai Lunas</button>
                                </x-confirm-form>
                            @endunless
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card cf-seksi">
        <div class="card-title">Honor Extras</div>
        <p style="font-size: var(--fs-xs); color: var(--text-muted); margin: 0 0 8px;">{{ $p->payments->whereNotNull('ditransfer_at')->count() }} dari {{ $p->payments->count() }} sudah ditransfer</p>
        <div class="table-container">
            <table>
                <thead><tr><th>Extras</th><th>Peran</th><th>Nominal (pokok + add-on)</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($p->payments as $pay)
                        @php $app = $pay->projectApplication; @endphp
                        <tr>
                            <td>{{ $app?->extras?->user?->username ? '@'.$app->extras->user->username : ($app?->extras?->user?->name ?? '-') }}</td>
                            <td>{{ $app?->karakter ?: '-' }}</td>
                            <td>{{ $rp($pay->nominalTotal()) }}</td>
                            <td><x-status-badge :model="$pay" /></td>
                            <td>@if ($app)<a href="{{ route('payments.show', $app) }}" class="btn btn-sm {{ $pay->bisaDitransfer() ? 'btn-brand' : '' }}">{{ $pay->bisaDitransfer() ? 'Transfer' : 'Lihat' }}</a>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="text-align: center; color: var(--text-muted);">Belum ada honor Extras.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card cf-seksi">
        <div class="card-title">Honor Staf</div>
        <div class="table-container">
            <table>
                <thead><tr><th>Staf</th><th>Role</th><th>Nominal</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($p->payrolls as $payroll)
                        @php $staf = $payroll->assignment?->user; @endphp
                        <tr>
                            <td>{{ $staf?->name ?? '-' }}</td>
                            <td>{{ $staf?->label() ?? '-' }}</td>
                            <td>{{ $rp($payroll->nominalTotal()) }}</td>
                            <td>
                                @if ($payroll->isDibayar())
                                    <span class="badge badge-aktif">Sudah Dibayar</span>
                                    <div style="font-size: var(--fs-xs); color: var(--text-muted);">{{ $payroll->dibayar_at?->translatedFormat('d M Y H:i') }}</div>
                                @else
                                    <span class="badge badge-pending">Belum Dibayar</span>
                                @endif
                            </td>
                            <td>
                                @unless ($payroll->isDibayar())
                                    <x-confirm-form :action="route('admin.payrolls.tandai-dibayar', $payroll)" method="PATCH" message="Tandai honor staf ini sudah dibayarkan?">
                                        <button type="submit" class="btn btn-sm btn-brand">Tandai Dibayar</button>
                                    </x-confirm-form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="text-align: center; color: var(--text-muted);">Belum ada honor staf.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card cf-seksi">
        <div class="card-title">Biaya Lain-lain</div>
        <div class="table-container">
            <table>
                <thead><tr><th>Tanggal</th><th>Keterangan</th><th>Nominal</th><th>Dicatat</th><th></th></tr></thead>
                <tbody>
                    @forelse ($p->expenses as $b)
                        <tr>
                            <td>{{ $b->tanggal->translatedFormat('d M Y') }}</td>
                            <td>{{ $b->label }}</td>
                            <td>{{ $rp($b->nominal) }}</td>
                            <td>{{ $b->pembuat?->name ?? '-' }}</td>
                            <td>
                                @if (auth()->user()->isSuperAdmin() || (int) $b->created_by === auth()->id())
                                    <x-confirm-form :action="route('admin.expenses.destroy', $b)" method="DELETE" :message="'Hapus biaya '.$b->label.'?'">
                                        <button type="submit" class="btn btn-sm btn-danger-outline">Hapus</button>
                                    </x-confirm-form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="text-align: center; color: var(--text-muted);">Belum ada biaya lain-lain.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($p->client_request_status === 'disetujui')
            <form method="POST" action="{{ route('admin.projects.expenses.store', $p) }}" class="form-row" style="margin-top: 12px; align-items: flex-end;">
                @csrf
                <div><label for="biaya-label">Keterangan <span class="wajib" aria-hidden="true">*</span></label><input type="text" id="biaya-label" name="label" maxlength="255" required placeholder="mis. Konsumsi, transport" value="{{ old('label') }}"></div>
                <div><label for="biaya-nominal">Nominal (Rp) <span class="wajib" aria-hidden="true">*</span></label><input type="number" id="biaya-nominal" name="nominal" min="1" step="1" required value="{{ old('nominal') }}"></div>
                <div><label for="biaya-tanggal">Tanggal <span class="wajib" aria-hidden="true">*</span></label><input type="date" id="biaya-tanggal" name="tanggal" required value="{{ old('tanggal', today()->toDateString()) }}"></div>
                <div style="flex: 0 0 auto;"><button type="submit" class="btn btn-brand">+ Tambah Biaya</button></div>
            </form>
        @endif
    </div>
@endif

<style>
    .detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 14px; }
    .cf-seksi { margin-bottom: 14px; }
    .cf-seksi td { white-space: nowrap; }
</style>
@endsection
