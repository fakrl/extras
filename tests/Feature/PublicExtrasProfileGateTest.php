<?php

namespace Tests\Feature;

use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicExtrasProfileGateTest extends TestCase
{
    use RefreshDatabase;

    private function profil(array $userAttrs = []): ExtrasProfile
    {
        Storage::fake('local');
        $user = User::factory()->create(array_merge(['role' => 'extras'], $userAttrs));
        $profile = ExtrasProfile::create(['user_id' => $user->id, 'foto_profil_path' => 'foto/'.$user->id.'.jpg']);
        Storage::disk('local')->put($profile->foto_profil_path, 'isi-foto-palsu');
        $profile->generateShareToken();

        return $profile->fresh();
    }

    public function test_foto_akun_aktif_bisa_diakses(): void
    {
        $profile = $this->profil(['status' => 'aktif']);

        $this->get(route('public.extras.foto', $profile->share_token))->assertOk();
    }

    public function test_foto_akun_nonaktif_404(): void
    {
        $profile = $this->profil(['status' => 'nonaktif']);

        $this->get(route('public.extras.foto', $profile->share_token))->assertNotFound();
    }

    public function test_foto_akun_melanggar_404(): void
    {
        $profile = $this->profil(['status' => 'melanggar']);

        $this->get(route('public.extras.foto', $profile->share_token))->assertNotFound();
    }

    public function test_foto_akun_soft_deleted_404(): void
    {
        $profile = $this->profil(['status' => 'aktif']);
        $profile->user->delete();

        $this->get(route('public.extras.foto', $profile->share_token))->assertNotFound();
    }

    public function test_foto_token_tidak_valid_404(): void
    {
        $this->get(route('public.extras.foto', 'token-ngasal-tidak-ada'))->assertNotFound();
    }

    public function test_profil_publik_akun_nonaktif_404(): void
    {
        $profile = $this->profil(['status' => 'nonaktif']);

        $this->get(route('public.extras.profile', $profile->share_token))->assertNotFound();
    }

    public function test_foto_tambahan_akun_nonaktif_404(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => 'extras', 'status' => 'nonaktif']);
        $profile = ExtrasProfile::create(['user_id' => $user->id, 'foto_profil_path' => 'foto/'.$user->id.'.jpg']);
        Storage::disk('local')->put('foto/tambahan-1.jpg', 'isi-foto-palsu');
        $profile->forceFill(['foto_tambahan' => ['1' => 'foto/tambahan-1.jpg']])->save();
        $profile->generateShareToken();

        $this->get(route('public.extras.foto-tambahan', [$profile->fresh()->share_token, 1]))->assertNotFound();
    }
}
