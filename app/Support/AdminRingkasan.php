<?php

namespace App\Support;

use App\Models\CastingProject;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\ProjectApplication;

// BL.1: satu sumber angka "perlu tindakan" Admin, dipakai dashboard Admin & pratinjau Monitoring SA.
class AdminRingkasan
{
    public static function untuk(?int $adminId = null): array
    {
        $proyek = fn ($q) => $q->when($adminId, fn ($q) => $q->whereHas('castingProject', fn ($p) => $p->where('admin_id', $adminId)));
        $lamaran = fn ($q) => $q->when($adminId, fn ($q) => $q->whereHas('projectApplication.castingProject', fn ($p) => $p->where('admin_id', $adminId)));
        $url = fn (array $q) => route('admin.projects.index', $q, false);

        return [
            'nego' => [
                'label' => 'Nego menunggu balasan Admin',
                'jumlah' => ProjectApplication::where('status_partisipasi', 'nego_fee')
                    ->whereHas('feeNegotiations', fn ($n) => $n->where('diajukan_oleh', 'extras')
                        ->whereRaw('round = (select max(f2.round) from fee_negotiations f2 where f2.project_application_id = fee_negotiations.project_application_id)'))
                    ->tap($proyek)->count(),
                'url' => $url(['peserta' => 'nego_fee']),
            ],
            'deal' => [
                'label' => 'Kandidat Deal siap diajukan ke Client',
                'jumlah' => ProjectApplication::where('status_partisipasi', 'deal')->tap($proyek)->count(),
                'url' => $url(['peserta' => 'deal']),
            ],
            'kontrak' => [
                'label' => 'Kontrak menunggu TTD Admin',
                'jumlah' => Contract::whereNull('ttd_admin_signature_path')->whereNull('voided_at')->tap($lamaran)->count(),
                'url' => $url(['peserta' => 'lolos']),
            ],
            'bayar' => [
                'label' => 'Pembayaran Extras belum ditransfer',
                'jumlah' => Payment::where('status', 'belum_dibayar')->tap($lamaran)->count(),
                'url' => $url(['bayar' => 'extras']),
            ],
            'tanpa_pic' => [
                'label' => 'Proyek tanpa PIC/Client',
                'jumlah' => CastingProject::where(fn ($q) => $q->whereNull('client_id')->orWhereNull('admin_id'))
                    ->when($adminId, fn ($q) => $q->where('admin_id', $adminId))->count(),
                'url' => $url(['tanpa_client' => 1]),
            ],
        ];
    }

    const TAHAP = [
        'ajuan' => ['label' => 'Ajuan', 'status' => ['diajukan', 'direview_admin'], 'filter' => ['peserta' => 'diajukan']],
        'nego' => ['label' => 'Nego Fee', 'status' => ['nego_fee', 'deal'], 'filter' => ['peserta' => 'nego_fee']],
        'client' => ['label' => 'Dipilih Client', 'status' => ['diajukan_ke_client'], 'filter' => ['peserta' => 'diajukan_ke_client']],
        'kontrak' => ['label' => 'Kontrak', 'status' => ['lolos'], 'filter' => ['peserta' => 'lolos']],
        'selesai' => ['label' => 'Selesai', 'status' => ['kontrak_ditandatangani'], 'filter' => ['bayar' => 'extras']],
    ];

    /** BP: step-bar tahapan kandidat + daftar siapa & aksi berikutnya, urut paling lama menunggu. */
    public static function tahapan(?int $adminId = null, int $maks = 8): array
    {
        $apps = ProjectApplication::whereIn('status_partisipasi', array_merge(...array_column(self::TAHAP, 'status')))
            ->when($adminId, fn ($q) => $q->whereHas('castingProject', fn ($p) => $p->where('admin_id', $adminId)))
            ->with(['extras.user', 'castingProject', 'castingProjectClass', 'contract', 'payment', 'feeNegotiations'])
            ->get()->map(fn ($a) => self::langkah($a))->filter();

        return collect(self::TAHAP)->map(function ($t, $key) use ($apps, $maks) {
            $isi = $apps->whereIn('app.status_partisipasi', $t['status']);

            return [
                'label' => $t['label'],
                'jumlah' => $isi->count(),
                'perlu' => $isi->where('perlu', true)->count(),
                'url' => route('admin.projects.index', $t['filter'], false),
                'daftar' => $isi->sortBy(fn ($i) => $i['sejak']?->timestamp ?? 0)->take($maks)->values(),
            ];
        })->all();
    }

    private static function langkah(ProjectApplication $a): ?array
    {
        $lineup = route('admin.projects.applicants', [$a->casting_project_id, 'q' => $a->extras?->user?->username], false).'#app-'.$a->id;
        $nego = $a->feeNegotiations->last();
        $kontrak = $a->contract;
        $bayar = $a->payment;
        $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');

        [$teks, $perlu, $sejak, $tombol, $url] = match ($a->status_partisipasi) {
            'diajukan' => ['Belum direview — beri grade / tolak', true, $a->updated_at, 'Review', $lineup],
            'direview_admin' => ['Sudah di-grade — ajukan fee awal', true, $a->updated_at, 'Ajukan fee', $lineup],
            'nego_fee' => $nego?->diajukan_oleh === 'extras'
                ? ['Extras counter '.$rp($nego->nominal).' — balas', true, $nego->created_at, 'Balas nego', route('admin.negotiations.show', $a, false)]
                : ['Menunggu balasan Extras', false, $nego?->created_at ?? $a->updated_at, 'Lihat nego', route('admin.negotiations.show', $a, false)],
            'deal' => ['Siap diajukan ke Client', true, $a->updated_at, 'Ajukan', $lineup],
            'diajukan_ke_client' => ['Menunggu keputusan Client', false, $a->updated_at, 'Lihat', $lineup],
            'lolos' => match (true) {
                ! $kontrak => ['Menunggu Extras lengkapi nama asli & NIK', false, $a->updated_at, 'Lihat', $lineup],
                ! $kontrak->ttd_admin_signature_path && ! $kontrak->voided_at => ['TTD Admin belum', true, $kontrak->created_at, 'Tanda tangan', route('contracts.show', $a, false)],
                default => ['Tunggu TTD Extras', false, $kontrak->updated_at, 'Lihat kontrak', route('contracts.show', $a, false)],
            },
            'kontrak_ditandatangani' => match ($bayar?->status) {
                'dikonfirmasi_diterima' => [null, false, null, null, null],
                'ditransfer' => ['Menunggu konfirmasi Extras', false, $bayar->ditransfer_at ?? $bayar->updated_at, 'Lihat', route('payments.show', $a, false)],
                'disengketakan' => ['Extras menyengketakan pembayaran — tinjau', true, $bayar->updated_at, 'Tinjau', route('payments.show', $a, false)],
                default => ['Honor belum ditransfer', true, $kontrak?->signed_at ?? $bayar?->created_at ?? $a->updated_at, 'Transfer', route('payments.show', $a, false)],
            },
        };

        return $teks ? ['app' => $a, 'teks' => $teks, 'perlu' => $perlu, 'sejak' => $sejak, 'tombol' => $tombol, 'url' => $url, 'lineup' => $lineup] : null;
    }
}
