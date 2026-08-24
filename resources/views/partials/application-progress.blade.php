@php
    // Urutan step utama pendaftaran Extras (RF-11 s.d. RF-24). 'ditolak' dan
    // 'dibatalkan' BUKAN bagian dari urutan ini — keduanya jalur keluar yang
    // ditangani terpisah di bawah (lihat CLAUDE.md alur status_partisipasi).
    $urutanStep = [
        'diajukan' => 'Diajukan',
        'direview_admin' => 'Direview Admin',
        'nego_fee' => 'Nego Fee',
        'deal' => 'Deal',
        'diajukan_ke_cd' => 'Diajukan ke CD',
        'direview_cd' => 'Direview CD',
        'lolos' => 'Lolos',
        'kontrak_ditandatangani' => 'Kontrak TTD',
        'selesai_produksi' => 'Selesai',
    ];

    $statusSaatIni = $app->status_partisipasi;
    $indexAktif = array_search($statusSaatIni, array_keys($urutanStep), true);
@endphp

@if (in_array($statusSaatIni, ['ditolak', 'dibatalkan'], true))
    <div class="step-bar-stopped">
        <i class="ti ti-circle-x"></i>
        <div>
            <div class="step-bar-stopped-title">
                {{ $statusSaatIni === 'ditolak' ? 'Tidak lolos seleksi' : 'Pendaftaran dibatalkan' }}
            </div>
            @if ($app->alasan_tolak)
                <div class="step-bar-stopped-reason">Alasan: {{ $app->alasan_tolak }}</div>
            @endif
        </div>
    </div>
@else
    <div class="step-bar-wrap">
        <div class="step-bar">
            @foreach ($urutanStep as $key => $label)
                @php
                    $i = array_search($key, array_keys($urutanStep), true);
                    $cssClass = $i < $indexAktif ? 'is-done' : ($i === $indexAktif ? 'is-active' : '');
                @endphp
                <div class="step-bar-item {{ $cssClass }}">
                    <div class="step-bar-circle">
                        @if ($i < $indexAktif)
                            <i class="ti ti-check"></i>
                        @else
                            {{ $i + 1 }}
                        @endif
                    </div>
                    <div class="step-bar-label">{{ $label }}</div>
                </div>
                @if (! $loop->last)
                    <div class="step-bar-line"></div>
                @endif
            @endforeach
        </div>
    </div>
@endif
