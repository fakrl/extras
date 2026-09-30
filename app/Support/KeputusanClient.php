<?php

namespace App\Support;

use App\Models\ClientReview;
use Illuminate\Database\Eloquent\Collection;

/** BR.2: feed keputusan Client lintas proyek, dipakai Admin (Kelola Akun ▸ Client, dashboard) & SA (dashboard). */
class KeputusanClient
{
    public static function terbaru(int $n = 10): Collection
    {
        return ClientReview::query()
            ->with(['client:id,name,nama_perusahaan', 'projectApplication:id,casting_project_id,extras_id', 'projectApplication.castingProject:id,nama_produksi,created_at', 'projectApplication.extras:id,user_id', 'projectApplication.extras.user:id,username'])
            ->latest()->latest('id')
            ->take($n)
            ->get();
    }
}
