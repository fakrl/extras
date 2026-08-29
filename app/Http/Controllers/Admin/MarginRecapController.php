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
     * Margin dihitung eksak per aplikasi: tiap ProjectApplication yang lolos
     * ke atas tahu kelasnya sendiri (casting_project_class_id), jadi
     * fee_client aplikasi itu = budget_client kelasnya, bukan lagi
     * budget_client x kuota_kelas di level proyek. Aplikasi lama/tanpa kelas
     * (casting_project_class_id null) tidak di-drop, masuk baris terpisah
     * "Belum terklasifikasi" supaya payout-nya tetap terlihat di total.
     */
    public function index()
    {
        $projects = CastingProject::with(['applications' => function ($q) {
            $q->whereIn('status_partisipasi', self::STATUS_LOLOS_KE_ATAS)->with('castingProjectClass');
        }])->get()->map(fn (CastingProject $project) => $this->hitungMargin($project));

        return view('admin.recap.margin', compact('projects'));
    }

    private function hitungMargin(CastingProject $project): object
    {
        $breakdown = collect();
        $belumTerklasifikasi = null;
        $totalFeeClient = 0.0;
        $totalPayout = 0.0;

        $tanpaKelas = $project->applications->whereNull('casting_project_class_id');

        if ($tanpaKelas->isNotEmpty()) {
            $payout = (float) $tanpaKelas->sum('fee_final');
            $totalPayout += $payout;

            $belumTerklasifikasi = (object) [
                'jumlah_aplikasi' => $tanpaKelas->count(),
                'total_payout' => $payout,
            ];
        }

        // groupBy('casting_project_class_id') memperlakukan kunci null sebagai
        // string kosong (bukan null), makanya null di-filter manual di atas
        // sebelum group ini dibentuk (cuma berisi aplikasi berkelas).
        foreach ($project->applications->whereNotNull('casting_project_class_id')->groupBy('casting_project_class_id') as $aplikasi) {
            $payout = (float) $aplikasi->sum('fee_final');
            $totalPayout += $payout;

            $feeClient = (float) $aplikasi->first()->castingProjectClass->budget_client * $aplikasi->count();
            $totalFeeClient += $feeClient;

            $breakdown->push((object) [
                'kelas' => $aplikasi->first()->castingProjectClass,
                'jumlah_aplikasi' => $aplikasi->count(),
                'total_fee_client' => $feeClient,
                'total_payout' => $payout,
                'margin' => $feeClient - $payout,
            ]);
        }

        $margin = $totalFeeClient - $totalPayout;

        return (object) [
            'project' => $project,
            'breakdown' => $breakdown,
            'belum_terklasifikasi' => $belumTerklasifikasi,
            'total_fee_client' => $totalFeeClient,
            'total_payout' => $totalPayout,
            'margin' => $margin,
            'margin_persen' => $totalFeeClient > 0 ? $margin / $totalFeeClient * 100 : 0,
        ];
    }
}
