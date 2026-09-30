<?php

namespace App\Exports;

use App\Models\ClientReview;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClientRiwayatExport implements FromCollection, WithHeadings
{
    public function __construct(private int $klienId, private int $castingProjectId) {}

    public function collection(): Collection
    {
        return ClientReview::where('client_id', $this->klienId)
            ->whereHas('projectApplication', fn ($q) => $q->where('casting_project_id', $this->castingProjectId))
            ->with(['projectApplication.extras:id,user_id', 'projectApplication.extras.user:id,username'])
            ->latest()
            ->get()
            ->map(fn ($r) => [
                'alias' => $r->projectApplication->extras->user->username ?? '-',
                'keputusan' => ucfirst($r->keputusan),
                'tanggal' => $r->created_at->format('d M Y'),
            ]);
    }

    public function headings(): array
    {
        return ['Alias', 'Keputusan', 'Tanggal'];
    }
}
