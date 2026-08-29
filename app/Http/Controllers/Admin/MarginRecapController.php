<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CastingProject;

class MarginRecapController extends Controller
{
    private const STATUS_LOLOS_KE_ATAS = ['lolos', 'kontrak_ditandatangani', 'selesai_produksi'];

    /**
     * RF-30: margin = rahasia bisnis inti, hanya Admin Default & Super Admin
     * (route middleware role:admin_default,super_admin, bukan grup admin umum).
     *
     * Catatan skema: project_applications tidak punya FK ke
     * casting_project_classes (dicek lewat migration asli, bukan asumsi),
     * jadi margin per kepala tidak bisa dihitung eksak per kelas per aplikasi.
     * Dihitung di level proyek: total fee client = budget_client x kuota_kelas
     * (nilai kontrak/budget yang disepakati per kelas), total payout = jumlah
     * fee_final aplikasi yang lolos ke atas. Didokumentasikan di DEV-NOTES.md.
     */
    public function index()
    {
        $projects = CastingProject::with('classes')
            ->withSum(['applications as total_payout' => function ($q) {
                $q->whereIn('status_partisipasi', self::STATUS_LOLOS_KE_ATAS);
            }], 'fee_final')
            ->get()
            ->map(function (CastingProject $project) {
                $totalFeeClient = $project->classes->sum(fn ($kelas) => $kelas->budget_client * $kelas->kuota_kelas);
                $totalPayout = (float) ($project->total_payout ?? 0);
                $margin = $totalFeeClient - $totalPayout;

                return (object) [
                    'project' => $project,
                    'total_fee_client' => $totalFeeClient,
                    'total_payout' => $totalPayout,
                    'margin' => $margin,
                    'margin_persen' => $totalFeeClient > 0 ? $margin / $totalFeeClient * 100 : 0,
                ];
            });

        return view('admin.recap.margin', compact('projects'));
    }
}
