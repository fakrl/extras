@extends('layouts.app')

@section('title', 'Kelola Proyek Casting')

@section('content')
<div class="card-header-row">
    <div>
        <div style="font-size: 16px; font-weight: 600;">Kelola Proyek</div>
        <div style="font-size: 12.5px; color: var(--text-secondary);">Semua proyek casting yang sedang & pernah dibuka</div>
    </div>
    <a href="{{ route('admin.projects.create') }}" class="btn btn-brand">+ Buka Lowongan Baru</a>
</div>

@if ($projects->isEmpty())
    <div class="card" style="text-align:center; color: var(--text-muted); padding: 30px 0;">
        Belum ada proyek casting. Klik "+ Buka Lowongan Baru" untuk membuat yang pertama.
    </div>
@else
    <div style="position: relative; margin-bottom: 16px;">
        <input type="text" id="search-projects" placeholder="Cari nama produksi, client PH, atau status..."
               style="width: 100%; max-width: 400px; padding: 8px 14px 8px 36px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 13.5px; background: var(--bg-card); color: var(--text-primary); margin-bottom: 0;">
        <i class="ti ti-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 15px;"></i>
    </div>

    <div id="no-projects-match" class="card" style="display: none; text-align: center; color: var(--text-muted); padding: 24px;">
        Tidak ada proyek yang sesuai dengan pencarian.
    </div>

    <div class="entity-card-grid" id="projects-grid">
        @foreach ($projects as $project)
            <div class="entity-card project-card" style="position: relative;"
                 data-search="{{ strtolower($project->nama_produksi . ' ' . $project->client_ph . ' ' . $project->status) }}">
                <details style="position: absolute; top: 10px; right: 10px; z-index: 10;">
                    <summary style="list-style: none; cursor: pointer; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-card);">
                        <i class="ti ti-dots-vertical"></i>
                    </summary>
                    <div style="position: absolute; right: 0; top: 36px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; min-width: 160px; box-shadow: 0 4px 12px rgba(0,0,0,.12); padding: 4px 0; z-index: 20;">
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
                <div class="entity-card-title">
                    {{ $project->nama_produksi }}
                    @if ($project->isUrgent())
                        <span class="badge badge-tolak">Urgent</span>
                    @endif
                </div>
                <div class="entity-card-sub">{{ $project->client_ph }}</div>

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
                    <span class="entity-card-row-label">Status</span>
                    <span class="entity-card-row-value">
                        <span class="badge {{ $project->status === 'dibuka' ? 'badge-aktif' : 'badge-tolak' }}">
                            {{ $project->status }}
                        </span>
                    </span>
                </div>
                <div class="entity-card-row">
                    <span class="entity-card-row-label">Pendaftar</span>
                    <span class="entity-card-row-value">{{ $project->applications_count }} orang</span>
                </div>

                <div class="entity-card-actions">
                    <a href="{{ route('admin.projects.applicants', $project) }}" class="btn btn-brand" style="flex: 1; text-align: center;">
                        Lihat Lineup ({{ $project->applications_count }})
                    </a>
                </div>
            </div>
        @endforeach
    </div>
@endif

<script>
    document.querySelectorAll('[data-copy-link]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            navigator.clipboard.writeText(btn.dataset.copyLink).then(function () {
                var original = btn.textContent;
                btn.textContent = 'Link disalin!';
                setTimeout(function () { btn.textContent = original; }, 2000);
            });
        });
    });

    // Real-time instant project search
    (function () {
        var searchInput = document.getElementById('search-projects');
        var cards = document.querySelectorAll('.project-card');
        var noMatch = document.getElementById('no-projects-match');
        if (!searchInput) return;

        searchInput.addEventListener('input', function () {
            var q = this.value.toLowerCase().trim();
            var visibleCount = 0;
            cards.forEach(function (card) {
                var text = card.dataset.search || card.textContent.toLowerCase();
                var match = !q || text.includes(q);
                card.style.display = match ? '' : 'none';
                if (match) visibleCount++;
            });
            if (noMatch) {
                noMatch.style.display = (visibleCount === 0 && q.length > 0) ? 'block' : 'none';
            }
        });
    })();
</script>
@endsection
