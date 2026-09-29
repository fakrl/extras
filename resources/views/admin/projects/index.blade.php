@extends('layouts.app')

@section('title', 'Proyek & Keuangan')

@section('content')
@php
    $rp = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
    $filterLain = array_filter([
        $peserta ? 'ada kandidat '.(\App\Models\ProjectApplication::LABELS[$peserta] ?? $peserta) : null,
        $bayar === 'staf' ? 'honor staf belum dibayar' : null,
        $bayar === 'extras' ? 'honor Extras belum ditransfer' : null,
        request('status') ? 'lowongan '.request('status') : null,
        request()->boolean('urgent') ? 'urgent' : null,
        request()->boolean('tanpa_client') ? 'Client belum diisi' : null,
    ]);
@endphp
<div class="card-header-row">
    <div>
        <div style="font-size: 16px; font-weight: 600;">Proyek &amp; Keuangan</div>
        <div style="font-size: var(--fs-sm); color: var(--text-secondary);">Semua proyek casting beserta uang masuk, piutang, keluar, saldo, dan proyeksinya</div>
        <div style="font-size: var(--fs-xs); color: var(--text-muted);">Saldo minus wajar kalau invoice belum dibayar — lihat Proyeksi.</div>
        @if ($filterLain)
            <div style="font-size: var(--fs-sm); margin-top: 4px;">Filter aktif: {{ implode(', ', $filterLain) }} · <a href="{{ route('admin.projects.index') }}">Hapus filter</a></div>
        @endif
    </div>
    <a href="{{ route('admin.projects.create') }}" class="btn btn-brand">+ Buat Proyek</a>
</div>

<form method="GET" action="{{ route('admin.projects.index') }}" class="xtoolbar" role="search" data-live>
    <input type="search" name="q" value="{{ $cari }}" class="xtoolbar-cari" placeholder="Cari nama produksi atau client..." aria-label="Cari proyek">
    @foreach (array_filter(['tahap' => $tahap, 'bayar' => $bayar, 'peserta' => $peserta, 'status' => request('status'), 'tanpa_client' => request('tanpa_client'), 'dari' => $periode ? $periode[0]->format('Y-m-d') : null, 'sampai' => $periode ? $periode[1]->format('Y-m-d') : null]) as $k => $v)
        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
    @endforeach
    <button type="submit" class="btn btn-sm"><i class="ti ti-search"></i> Cari</button>
</form>

<div data-live-target>

<div class="xfilter" aria-label="Filter tahap proyek">
    @foreach (['' => 'Semua'] + \App\Models\CastingProject::TAHAP as $value => $label)
        <a href="{{ request()->fullUrlWithQuery(['tahap' => $value ?: null, 'page' => null]) }}"
           class="btn btn-sm {{ ($tahap ?? '') === $value ? 'btn-brand' : '' }}" @if (($tahap ?? '') === $value) aria-current="true" @endif>{{ $label }}</a>
    @endforeach
</div>

@if ($periode)
    <div class="xfilter">
        <a href="{{ request()->fullUrlWithQuery(['dari' => null, 'sampai' => null, 'page' => null]) }}" class="btn btn-sm btn-brand" aria-label="Hapus filter periode">
            Periode: {{ $periode[0]->translatedFormat($periode[0]->year === $periode[1]->year ? 'd M' : 'd M Y') }}–{{ $periode[1]->translatedFormat('d M Y') }} ×
        </a>
        <span class="xfilter-label">shooting dalam periode</span>
    </div>
@endif

@if ($projects->isEmpty())
    <div class="card" style="text-align:center; color: var(--text-muted); padding: 30px 0;">
        {{ $cari !== '' || $tahap || $periode || $filterLain ? 'Tidak ada proyek yang sesuai filter.' : 'Belum ada proyek casting. Klik "+ Buat Proyek" untuk membuat yang pertama.' }}
    </div>
@else
    <div class="entity-card-grid" id="projects-grid">
        @foreach ($projects as $project)
            @php
                $cf = $cashflow[$project->id];
                $tahapProyek = $project->tahap();
            @endphp
            <div class="entity-card project-card" style="position: relative;">
                <details style="position: absolute; top: 10px; right: 10px; z-index: 10;">
                    <summary aria-label="Menu proyek" style="list-style: none; cursor: pointer; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-card);">
                        <i class="ti ti-dots-vertical"></i>
                    </summary>
                    <div style="position: absolute; right: 0; top: 36px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; min-width: 160px; box-shadow: 0 4px 12px rgba(0,0,0,.12); padding: 4px 0; z-index: 20;">
                        <a href="{{ route('admin.projects.show', $project) }}" style="display: block; padding: 8px 14px; font-size: 13px; color: var(--text-primary); text-decoration: none;">Detail Proyek</a>
                        <a href="{{ route('admin.projects.show', [$project, 'tab' => 'cashflow']) }}" style="display: block; padding: 8px 14px; font-size: 13px; color: var(--text-primary); text-decoration: none;">Cashflow</a>
                        <a href="{{ route('admin.projects.edit', $project) }}" style="display: block; padding: 8px 14px; font-size: 13px; color: var(--text-primary); text-decoration: none;">Edit Proyek</a>
                        @if ($project->status === 'dibuka' && $project->share_token)
                            <button type="button" style="display: block; width: 100%; padding: 8px 14px; font-size: 13px; text-align: left; background: none; border: none; color: var(--text-primary); cursor: pointer;" data-copy-link="{{ url('/event/'.$project->share_token) }}">Copy Link Pendaftaran</button>
                        @endif
                        <form method="POST" action="{{ route('admin.projects.toggle-status', $project) }}">
                            @csrf @method('PATCH')
                            <button style="display: block; width: 100%; padding: 8px 14px; font-size: 13px; text-align: left; background: none; border: none; color: var(--text-primary); cursor: pointer;">
                                {{ $project->status === 'dibuka' ? 'Tutup Lowongan' : 'Buka Lagi' }}
                            </button>
                        </form>
                        <a href="{{ route('invoices.show', $project) }}" style="display: block; padding: 8px 14px; font-size: 13px; color: var(--text-primary); text-decoration: none;">Invoice</a>
                    </div>
                </details>
                <div class="entity-card-title" style="padding-right: 40px;">
                    <a href="{{ route('admin.projects.show', $project) }}" style="color: inherit; text-decoration: none;">{{ $project->nama_produksi }}</a>
                    @if ($project->isUrgent())
                        <span class="badge badge-tolak">Urgent</span>
                    @endif
                    @if (! $project->client_id)
                        <span class="badge badge-pending">Client belum diisi</span>
                    @endif
                </div>
                <div class="entity-card-sub">
                    {{ $project->client?->name ?? $project->client_ph }}{{ $project->client && $project->client_ph !== $project->client->name ? ' · '.$project->client_ph : '' }}
                    · PIC {{ $project->admin?->name ?? '-' }}
                </div>

                <div class="entity-card-row">
                    <span class="entity-card-row-label">Tahap</span>
                    <span class="entity-card-row-value">
                        @if ($tahapProyek)
                            <span class="badge {{ \App\Models\CastingProject::TAHAP_BADGES[$tahapProyek] }}">{{ \App\Models\CastingProject::TAHAP[$tahapProyek] }}</span>
                        @else
                            <span class="badge badge-tolak">{{ ucfirst($project->client_request_status) }}</span>
                        @endif
                    </span>
                </div>
                <div class="entity-card-row">
                    <span class="entity-card-row-label">Shooting</span>
                    <span class="entity-card-row-value">{{ $project->rentangShooting() }}</span>
                </div>

                @if ($project->wa_group_link)
                    <div class="entity-card-row">
                        <span class="entity-card-row-label">Grup WA</span>
                        <span class="entity-card-row-value">
                            <a href="{{ $project->wa_group_link }}" target="_blank">Buka Link</a>
                        </span>
                    </div>
                @endif

                <div class="entity-card-row">
                    <span class="entity-card-row-label">Deadline</span>
                    <span class="entity-card-row-value">{{ $project->deadline->format('d M Y') }}</span>
                </div>
                <div class="entity-card-row">
                    <span class="entity-card-row-label">Lowongan</span>
                    <span class="entity-card-row-value">
                        <span class="badge {{ $project->status === 'dibuka' ? 'badge-aktif' : 'badge-tolak' }}">
                            {{ $project->status }}
                        </span>
                    </span>
                </div>
                <div class="entity-card-row">
                    <span class="entity-card-row-label">Pendaftar / kuota</span>
                    <span class="entity-card-row-value">{{ $project->applications_count }} / {{ $project->kuota }}</span>
                </div>
                <a href="{{ route('admin.projects.show', [$project, 'tab' => 'cashflow']) }}" class="proyek-uang" title="Buka cashflow proyek">
                    <span><small>Masuk</small>{{ $rp($cf->total_masuk) }}</span>
                    <span><small>Piutang</small>{{ $rp($cf->piutang) }}</span>
                    <span title="Dashboard cuma menghitung yang sudah dibayar dalam periode; di proyek dihitung semua kewajiban."><small>Keluar &#9432;</small>{{ $rp($cf->total_keluar) }}<small>Sudah dibayar {{ $rp($cf->keluar_dibayar) }}</small><small>Belum dibayar {{ $rp($cf->keluar_belum) }}</small></span>
                    <span><small>Saldo</small><b style="color: {{ $cf->saldo >= 0 ? 'var(--accent-strong)' : 'var(--danger)' }};">{{ $rp($cf->saldo) }}</b></span>
                    <span><small>Proyeksi</small><b style="color: {{ $cf->proyeksi >= 0 ? 'var(--accent-strong)' : 'var(--danger)' }};">{{ $rp($cf->proyeksi) }}</b></span>
                </a>

                <div class="entity-card-actions">
                    <a href="{{ route('admin.projects.show', $project) }}" class="btn" style="flex: 1; text-align: center;">Detail</a>
                    <a href="{{ route('admin.projects.applicants', [$project, 'status' => $peserta]) }}" class="btn btn-brand" style="flex: 1; text-align: center;">
                        Lihat Lineup ({{ $project->applications_count }})
                    </a>
                </div>
            </div>
        @endforeach
    </div>
    {{ $projects->links() }}
@endif
</div>

<style>
    .proyek-uang { display: flex; flex-wrap: wrap; gap: 4px 16px; margin-top: 10px; padding: 8px 10px; border-radius: 8px; background: var(--bg-page); color: var(--text-primary); text-decoration: none; font-size: var(--fs-sm); font-weight: 600; }
    .proyek-uang > span { white-space: nowrap; }
    .proyek-uang small { display: block; font-size: var(--fs-xs); font-weight: 500; color: var(--text-muted); }
</style>

<script>
    // delegasi: tombol tetap jalan setelah daftar diganti live search
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-copy-link]');
        if (!btn) return;
        navigator.clipboard.writeText(btn.dataset.copyLink).then(function () {
            var original = btn.textContent;
            btn.textContent = 'Link disalin!';
            setTimeout(function () { btn.textContent = original; }, 2000);
        });
    });
</script>
@endsection
