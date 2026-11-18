<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientReviewProfilModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_popup_profil_talent_lolos_memuat_tanpa_error_untuk_client_pemilik_proyek(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $project = CastingProject::factory()->create(['client_id' => $client->id, 'client_request_status' => 'disetujui']);
        $kelas = $project->classes()->create(['nama_kelas' => 'Umum', 'budget_client' => 100000, 'kuota_kelas' => 5]);
        $extras = ExtrasProfile::create([
            'user_id' => User::factory()->create(['role' => 'extras', 'username' => 'bruce_lee_demo'])->id,
            'nama_asli' => 'KTP bruce_lee_demo',
            'tautan_tambahan' => [['label' => 'Trailer', 'url' => 'https://www.youtube.com/watch?v=tB_7kK9r_QY']],
        ]);
        ProjectApplication::create([
            'casting_project_id' => $project->id,
            'casting_project_class_id' => $kelas->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'lolos',
        ]);

        // reproduksi static: halaman review client + modal profil (partial) sama-sama berhasil dimuat,
        // termasuk kandidat yang punya tautan_tambahan berisi link YouTube - tidak nemu kondisi yang bikin 500/stuck
        $this->actingAs($client)->get(route('client.reviews.show', $project))->assertOk();
        $this->actingAs($client)->get(route('client.extras.profil', ['user' => $extras->user_id, 'partial' => 1]))
            ->assertOk()
            ->assertSee('bruce_lee_demo');
    }
}
