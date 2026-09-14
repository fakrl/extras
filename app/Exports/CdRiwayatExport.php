<?php

namespace App\Exports;

use App\Models\CdReview;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CdRiwayatExport implements FromCollection, WithHeadings
{
    public function __construct(private int $cdId, private int $castingProjectId) {}

    public function collection(): Collection
    {
        return CdReview::where('cd_id', $this->cdId)
            ->whereHas('projectApplication', fn ($q) => $q->where('casting_project_id', $this->castingProjectId))
            ->with(['projectApplication.extras:id,alias'])
            ->latest()
            ->get()
            ->map(fn ($r) => [
                'alias' => $r->projectApplication->extras->alias ?? '-',
                'keputusan' => ucfirst($r->keputusan),
                'tanggal' => $r->created_at->format('d M Y'),
            ]);
    }

    public function headings(): array
    {
        return ['Alias', 'Keputusan', 'Tanggal'];
    }
}
