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
}
