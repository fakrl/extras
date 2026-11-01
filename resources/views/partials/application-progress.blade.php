@php
    $stepMap = [
        'diajukan'               => 1,
        'direview_admin'         => 1,
        'nego_fee'               => 2,
        'deal'                   => 2,
        'diajukan_ke_client'     => 3,
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
    $hasNik = !empty($app->extras?->nik_hash) || (!empty(auth()->user()?->extrasProfile?->nik_hash));
    $shootingDates = $app->relationLoaded('castingProject') && $app->castingProject
        ? ($app->castingProject->relationLoaded('shootingDates') ? $app->castingProject->shootingDates : $app->castingProject->shootingDates()->get())
        : ($app->castingProject ? $app->castingProject->shootingDates : collect());
    $isHariH = $shootingDates->contains(fn ($d) => $d->tanggal && $d->tanggal->isToday());
    $isPaymentDitransfer = $app->payment?->status === 'ditransfer';
@endphp

@if ($app->status_partisipasi === 'diundang')
    <div class="alert-info" style="margin: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
            <div><i class="ti ti-mail"></i> <strong>Undangan Terbuka untukmu</strong></div>
            <div style="font-size: var(--fs-xs); margin-top: 2px;">Kamu mendapat undangan langsung dari Admin untuk peran ini. Silakan terima atau tolak undangan.</div>
        </div>
        @if ($app->castingProject)
            <a href="{{ route('extras.projects.show', $app->castingProject) }}" class="btn btn-brand">Jawab Undangan Casting</a>
        @endif
    </div>
@elseif ($isStopped)
    <div class="step-bar-stopped">
        <i class="ti ti-circle-x"></i>
        <div style="flex: 1;">
            <div class="step-bar-stopped-title">
                {{ $app->status_partisipasi === 'ditolak' ? 'Tidak lolos seleksi' : 'Pendaftaran dibatalkan' }}
            </div>
            @if ($app->alasan_tolak)
                <div class="step-bar-stopped-reason">Alasan: {{ $app->alasan_tolak }}</div>
            @endif
            <div style="margin-top: 10px;">
                <a href="{{ route('extras.projects.index') }}" class="btn btn-sm btn-tonal" style="display: inline-flex; align-items: center; gap: 4px;">
                    <i class="ti ti-search"></i> Cari Lowongan Casting Lainnya <i class="ti ti-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
@else
    {{-- CU.3: ap-progress/.ap-progress-bar = modifier biar 5 langkah muat tanpa scroll di 390px, tanpa ganggu .step-bar bawaan --}}
    <div class="step-bar-wrap ap-progress">
        <div class="step-bar ap-progress-bar">
            @foreach ($stepLabels as $i => $label)
                @php
                    $stepIndex = $i + 1;
                    $cssClass = $stepIndex < $currentStep ? 'is-done' : ($stepIndex === $currentStep ? 'is-active' : '');
                @endphp
                <div class="step-bar-item {{ $cssClass }}">
                    <div class="step-bar-circle">
                        @if ($stepIndex < $currentStep)
                            <i class="ti ti-check"></i>
                        @else
                            {{ $stepIndex }}
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

    {{-- Next-Action Callout: Memberikan panduan langkah selanjutnya yang jelas --}}
    <div class="next-action-callout @if(in_array($app->status_partisipasi, ['nego_fee', 'lolos']) || ($app->status_partisipasi === 'selesai_produksi' && $isPaymentDitransfer)) nac-primary @else nac-info @endif">
        <div class="nac-content">
            @if (in_array($app->status_partisipasi, ['diajukan', 'direview_admin']))
                <div class="nac-title"><i class="ti ti-clock"></i> Menunggu Review Admin</div>
                <div class="nac-desc">Lamaranmu sudah masuk dan sedang diperiksa oleh Admin JBTB. Kamu belum perlu melakukan apa-apa saat ini.</div>
            @elseif ($app->status_partisipasi === 'nego_fee')
                <div class="nac-title"><i class="ti ti-cash"></i> Giliranmu: Kesepakatan Honor</div>
                <div class="nac-desc">Admin mengajukan penawaran honor untukmu. Silakan tinjau nominalnya, setujui, atau ajukan penawaran balik.</div>
            @elseif (in_array($app->status_partisipasi, ['deal', 'diajukan_ke_client']))
                <div class="nac-title"><i class="ti ti-sparkles"></i> Honor Disepakati · Menunggu Keputusan Client</div>
                <div class="nac-desc">Nominal honor sudah disepakati! Profilmu sedang diajukan ke pihak PH/Client untuk pemilihan akhir. Mohon tunggu pengumuman hasilnya.</div>
            @elseif ($app->status_partisipasi === 'lolos' && ! $hasNik)
                <div class="nac-title"><i class="ti ti-id"></i> Selamat, Kamu Terpilih! (Langkah 1 dari 2)</div>
                <div class="nac-desc">Sebelum tanda tangan kontrak kerja, lengkapi data NIK/KTP-mu terlebih dahulu.</div>
            @elseif ($app->status_partisipasi === 'lolos')
                <div class="nac-title"><i class="ti ti-file-certificate"></i> Selamat, Kamu Terpilih! (Langkah 2 dari 2)</div>
                <div class="nac-desc">Langkah terakhir sebelum syuting: baca dan tanda tangani kontrak kerja digitalmu.</div>
            @elseif ($app->status_partisipasi === 'kontrak_ditandatangani')
                <div class="nac-title"><i class="ti ti-circle-check"></i> Kontrak Kerja Aktif</div>
                <div class="nac-desc">Kontrak sudah aktif. Pantau jadwal Callsheet dan bersiap untuk jadwal syuting di lokasi.</div>
            @elseif ($app->status_partisipasi === 'selesai_produksi' && $isPaymentDitransfer)
                <div class="nac-title"><i class="ti ti-wallet"></i> Honor Sudah Ditransfer</div>
                <div class="nac-desc">Honor syutingmu sudah ditransfer oleh Admin. Silakan cek rekeningmu dan konfirmasi penerimaan.</div>
            @elseif ($app->status_partisipasi === 'selesai_produksi')
                <div class="nac-title"><i class="ti ti-circle-check"></i> Produksi Selesai</div>
                <div class="nac-desc">Syuting untuk proyek ini telah selesai. Terima kasih atas partisipasi dan kerja samanya!</div>
            @endif
        </div>

        <div class="nac-action">
            @if ($app->status_partisipasi === 'nego_fee')
                <a href="{{ route('extras.negotiations.show', $app) }}" class="btn btn-brand"><i class="ti ti-message-dollar"></i> Buka Ruang Nego Honor</a>
            @elseif ($app->status_partisipasi === 'lolos' && ! $hasNik)
                <a href="{{ route('extras.kontrak.lengkapi-ktp', $app) }}" class="btn btn-brand"><i class="ti ti-id"></i> Langkah 1/2: Lengkapi KTP</a>
            @elseif ($app->status_partisipasi === 'lolos')
                <a href="{{ route('contracts.show', $app) }}" class="btn btn-brand"><i class="ti ti-file-text"></i> Tanda Tangani Kontrak Kerja</a>
            @elseif ($app->status_partisipasi === 'selesai_produksi' && $isPaymentDitransfer)
                <a href="{{ route('payments.show', $app) }}" class="btn btn-brand"><i class="ti ti-check"></i> Cek & Konfirmasi Honor</a>
            @elseif (in_array($app->status_partisipasi, ['kontrak_ditandatangani', 'selesai_produksi']))
                <a href="{{ route('payments.show', $app) }}" class="btn btn-sm btn-tonal"><i class="ti ti-receipt"></i> Rincian Honor</a>
            @endif
        </div>
    </div>
@endif
