@php
    $stepMap = [
        'diajukan'               => 1,
        'direview_admin'         => 1,
        'nego_fee'               => 2,
        'deal'                   => 2,
        'diajukan_ke_client'         => 3,
        'lolos'                  => 4,
        'kontrak_ditandatangani' => 5,
        'selesai_produksi'       => 6,
    ];
    $stepLabels = ['Diajukan', 'Nego fee', 'Dipilih', 'Kontrak', 'Selesai'];
    if ($app->status_partisipasi === 'diajukan_ke_client') {
        $stepLabels[2] = 'Menunggu Client';
    }
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
    {{-- CU.3: ap-progress/.ap-progress-bar = modifier biar 5 langkah muat tanpa scroll di 390px, tanpa ganggu .step-bar bawaan (tahap-bar Admin & "Cara Kerja" tetap scroll). --}}
    <div class="step-bar-wrap ap-progress">
        <div class="step-bar ap-progress-bar">
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
