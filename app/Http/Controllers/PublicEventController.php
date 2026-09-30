<?php

namespace App\Http\Controllers;

use App\Models\CastingProject;
use Illuminate\Http\Request;

class PublicEventController extends Controller
{
    /**
     * RF-56: landing page publik per proyek, link-nya dibagikan Admin lewat
     * WA, kemungkinan dibuka belakangan setelah proyek closed, jadi token
     * salah/proyek sudah tidak menerima pendaftaran tetap tampil halaman
     * graceful, BUKAN 404/500 mentah.
     *
     * nama Client & budget_client SENGAJA tidak pernah dikirim ke view ini
     * (CLAUDE.md §5 + SPEC.md Bagian B, rahasia di semua permukaan publik).
     */
    public function show(Request $request, string $token)
    {
        $project = CastingProject::where('share_token', $token)->first();
        $valid = $project && $project->menerimaPendaftaran();

        if (! $valid) {
            return view('public.event', ['valid' => false]);
        }

        $user = $request->user();

        // Login tapi bukan Extras (mis. Admin/Client buka link ini), tidak
        // punya alur apply, lempar ke dashboard masing-masing daripada
        // menampilkan CTA yang tidak bisa mereka pakai.
        if ($user && ! $user->isExtras()) {
            return redirect($user->dashboardUrl());
        }

        $project->load([
            'classes' => fn ($q) => $q->select('id', 'casting_project_id', 'nama_kelas', 'kriteria', 'kuota_kelas')
                ->withTerisi()->with('categories:id,nama'),
            'shootingDates:id,casting_project_id,tanggal',
        ]);

        return view('public.event', [
            'valid' => true,
            'project' => $project,
            'sudahLoginExtras' => (bool) $user,
        ]);
    }
}
