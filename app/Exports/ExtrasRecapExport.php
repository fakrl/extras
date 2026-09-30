<?php

namespace App\Exports;

use App\Models\User;
use App\Support\FilterAkun;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * RF-52: Admin Default mengekspor data rekap ke format Excel.
 * BR.1: isi mengikuti filter aktif di Kelola Akun ▸ Extras.
 */
class ExtrasRecapExport implements FromCollection, WithHeadings
{
    public function __construct(private array $filter) {}

    public function collection(): Collection
    {
        return FilterAkun::terapkan(User::query(), $this->filter)
            ->with(['extrasProfile' => fn ($q) => $q->withTerpilih()->withBatalMendadak()])
            ->get()
            ->map(fn ($u) => [
                'alias' => $u->username ?? '-',
                'status' => $u->extrasProfile->status ?? $u->status,
                'jumlah_terpilih' => $u->extrasProfile->terpilih_count ?? 0,
                'cancel_count' => $u->extrasProfile->cancel_count ?? 0,
                'rate_card' => $u->extrasProfile?->rate_card,
            ]);
    }

    public function headings(): array
    {
        return ['Alias', 'Status', 'Jumlah Terpilih', 'Jumlah Pembatalan', 'Rate Card'];
    }
}
