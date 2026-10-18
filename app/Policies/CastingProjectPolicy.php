<?php

namespace App\Policies;

use App\Models\CastingProject;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * CM.2.1: satu-satunya gerbang aksi tulis Admin/Korlap atas proyek,
 * dipanggil lewat $this->authorize('kelola', $project) di controller.
 */
class CastingProjectPolicy
{
    public function kelola(User $user, CastingProject $project): Response
    {
        return $project->dikelolaOleh($user)
            ? Response::allow()
            : Response::deny('Proyek ini bukan tanggung jawab Anda.');
    }
}
