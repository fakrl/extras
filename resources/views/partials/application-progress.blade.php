@php
    $stepMap = [
        'diajukan'               => 1,
        'direview_admin'         => 1,
        'nego_fee'               => 2,
        'deal'                   => 2,
        'diajukan_ke_client'         => 3,
        'lolos'                  => 3,
        'kontrak_ditandatangani' => 4,
        'selesai_produksi'       => 5,
    ];
    $stepLabels = ['Ajuan/Antrian', 'Deal Nego Fee', 'Dipilih Client', 'Kontrak', 'Selesai'];
    $currentStep = $stepMap[$app->status_partisipasi] ?? 0;
    $isStopped = in_array($app->status_partisipasi, ['ditolak', 'dibatalkan']);
@endphp

@if ($app->status_partisipasi === 'diundang')
    <div class="alert-info" style="margin: 0;"><i class="ti ti-mail"></i> Menunggu jawabanmu atas undangan ini.</div>
@elseif ($isStopped)
    <div class="step-bar-stopped">
        <i class="ti ti-circle-x"></i>
        <div>
            <div class="step-bar-stopped-title">
                {{ $app->status_partisipasi === 'ditolak' ? 'Tidak lolos seleksi' : 'Pendaftaran dibatalkan' }}
            </div>
            @if ($app->alasan_tolak)
                <div class="step-bar-stopped-reason">Alasan: {{ $app->alasan_tolak }}</div>
            @endif
        </div>
    </div>
@else
    <div class="step-bar-wrap">
        <div class="step-bar">
            @foreach ($stepLabels as $i => $label)
                @php
                    $i = $i + 1;
                    $cssClass = $i < $currentStep ? 'is-done' : ($i === $currentStep ? 'is-active' : '');
                @endphp
                <div class="step-bar-item {{ $cssClass }}">
                    <div class="step-bar-circle">
                        @if ($i < $currentStep)
                            <i class="ti ti-check"></i>
                        @else
                            {{ $i }}
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
