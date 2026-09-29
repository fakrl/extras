<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * SPEC BB: halaman pribadi noindex, preview share (OG) di event/profil/homepage.
 */
class SeoMetaTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_punya_og_dan_noindex(): void
    {
        $project = CastingProject::create([
            'admin_id' => User::factory()->create(['role' => 'admin'])->id,
            'nama_produksi' => 'Iklan Minuman Segar',
            'client_ph' => 'PH Rahasia',
            'share_token' => Str::random(32),
            'deadline' => now()->addDays(7),
            'kuota' => 5,
            'status' => 'dibuka',
        ]);

        $this->get(route('public.event.show', $project->share_token))
            ->assertOk()
            ->assertSee('<meta property="og:title" content="Iklan Minuman Segar">', false)
            ->assertSee('noindex, nofollow', false)
            ->assertDontSee('PH Rahasia');

        $this->get(route('public.event.show', 'token-salah'))
            ->assertSee('<meta property="og:title" content="Pendaftaran Ditutup">', false);
    }

    public function test_profil_publik_noindex_dan_homepage_boleh_diindex(): void
    {
        $user = User::factory()->create(['role' => 'extras', 'username' => 'dimas.rk']);
        $token = ExtrasProfile::create(['user_id' => $user->id])->generateShareToken();

        $this->get(route('public.extras.profile', $token))
            ->assertOk()
            ->assertSee('noindex, nofollow', false)
            ->assertSee('@dimas.rk di JBTB', false);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('noindex', false)
            ->assertSee('rel="canonical"', false)
            ->assertSee('property="og:image"', false);
    }
}
