<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeWajibGantiPublikTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_publik_tetap_bisa_dibuka_dashboard_diarahkan_ubah_password(): void
    {
        $user = User::factory()->create(['role' => 'extras', 'wajib_ganti_password' => true]);
        $token = ExtrasProfile::create(['user_id' => $user->id])->generateShareToken();
        $project = CastingProject::create([
            'admin_id' => User::factory()->create(['role' => 'admin'])->id,
            'nama_produksi' => 'Proyek Publik', 'client_ph' => 'PH',
            'deadline' => now()->addDays(7), 'kuota' => 5, 'status' => 'dibuka',
            'share_token' => 'tok-publik',
        ]);

        $this->actingAs($user);
        foreach (['/', "/event/{$project->share_token}", "/p/extras/{$token}", '/privacy-policy'] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get('/extras/dashboard')->assertRedirect(route('ubah-password'));
    }
}
