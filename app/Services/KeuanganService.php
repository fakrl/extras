<?php

namespace App\Services;

use App\Models\CastingProject;
use App\Models\StaffPayroll;

class KeuanganService
{
    /**
     * SPEC AY.1.15: total honor staf yang belum dibayar (pokok + addon),
     * single source of truth dipakai dashboard Super Admin & tab Honor Staf.
     */
    public function totalHonorStafBelumDiproses(): float
    {
        return StaffPayroll::where('status_bayar', 'belum')
            ->with('addons')
            ->get()
            ->sum(fn (StaffPayroll $p) => $p->nominalTotal());
    }

    /**
     * Rincian invoice per kelas (peran x jumlah x fee = subtotal), dipakai
     * sama-sama oleh PDF invoice dan halaman invoices/show Client. Basis
     * kuota_kelas x budget_client, BUKAN jumlah aplikasi aktual (D5 belum
     * diputus, rumus ini yang dipakai PDF invoice sekarang).
     */
    public function rincianInvoice(CastingProject $project): object
    {
        $rows = $project->classes->map(fn ($class) => (object) [
            'nama_kelas' => $class->nama_kelas,
            'kuota_kelas' => $class->kuota_kelas,
            'budget_client' => (float) $class->budget_client,
            'subtotal' => (float) $class->budget_client * $class->kuota_kelas,
        ]);

        return (object) [
            'rows' => $rows,
            'total' => $rows->sum('subtotal'),
        ];
    }

    /**
     * BV.2/CN.3.4: usulan awal nominal invoice. Pakai budget_total (diisi Client
     * saat pengajuan) bila terisi, kalau tidak rumus lama (budget_client x kuota_kelas).
     * Kepemilikan invoice tidak diubah di sini (ditahan, lihat SPEC CN).
     */
    public function nilaiInvoice(CastingProject $project): float
    {
        return $project->budget_total !== null
            ? (float) $project->budget_total
            : (float) $this->rincianInvoice($project)->total;
    }
}
