<?php

namespace App\Support;

use App\Models\ClientReview;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/** BR.2: feed keputusan Client lintas proyek, dipakai Admin (Kelola Akun ▸ Client, dashboard) & SA (dashboard). */
class KeputusanClient
{
    public static function terbaru(int $n = 10): Collection
    {
        return self::query()->take($n)->get();
    }

    public static function halaman(int $per, string $pageName = 'kp'): LengthAwarePaginator
    {
        return self::query()->paginate($per, ['*'], $pageName);
    }

    private static function query(): Builder
    {
        return ClientReview::query()
            ->with(['client:id,name,nama_perusahaan', 'projectApplication:id,casting_project_id,extras_id', 'projectApplication.castingProject:id,nama_produksi,created_at', 'projectApplication.extras:id,user_id', 'projectApplication.extras.user:id,username'])
            ->latest()->latest('id');
    }
}
