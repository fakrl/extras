<?php

namespace App\Exports;

use App\Models\ExtrasProfile;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * RF-52: Admin Default mengekspor data rekap ke format Excel.
 */
class ExtrasRecapExport implements FromCollection, WithHeadings
{
    public function collection(): Collection
    {
        return ExtrasProfile::withCount([
            'applications' => fn ($q) => $q->whereIn('status_partisipasi', [
                'lolos', 'kontrak_ditandatangani', 'selesai_produksi',
            ]),
        ])
            ->orderByDesc('applications_count')
            ->get()
            ->map(fn ($ex) => [
                'alias' => $ex->alias,
                'status' => $ex->status,
                'jumlah_terpilih' => $ex->applications_count,
                'cancel_count' => $ex->cancel_count,
                'rate_card' => $ex->rate_card,
            ]);
    }

    public function headings(): array
    {
        return ['Alias', 'Status', 'Jumlah Terpilih', 'Jumlah Pembatalan', 'Rate Card'];
    }
}
