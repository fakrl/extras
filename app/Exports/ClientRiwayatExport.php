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
            ->with(['projectApplication.extras:id,user_id', 'projectApplication.extras.user:id,username', 'projectApplication.castingProjectClass:id,nama_kelas'])
            ->latest()
            ->get()
            ->map(fn ($r) => [
                'alias' => $r->projectApplication->extras->user->username ?? '-',
                'peran' => $r->projectApplication->castingProjectClass?->nama_kelas ?? '-',
                'grade' => $r->grade_client ?? '-',
                'keputusan' => $r->label(),
                'status' => $r->projectApplication->labelClient(),
                'tanggal' => $r->created_at->translatedFormat('d M Y'),
            ]);
    }

    public function headings(): array
    {
        return ['Alias', 'Peran', 'Grade Anda', 'Keputusan', 'Status', 'Tanggal'];
    }
}
