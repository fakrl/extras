<?php

namespace Tests\Feature;

use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class LoginGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_akun_nonaktif_gagal_login(): void
    {
        $user = User::factory()->create(['role' => 'admin_default', 'status' => 'nonaktif', 'password' => bcrypt('password')]);

        $response = $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertRedirect('/login');
        $this->assertFalse(Auth::check());
    }

    public function test_extras_melanggar_gagal_login(): void
    {
        $user = User::factory()->create(['role' => 'extras', 'password' => bcrypt('password')]);
        $profile = ExtrasProfile::create(['user_id' => $user->id, 'alias' => 'Alias Test']);
        $profile->forceFill(['status' => 'melanggar'])->save();

        $response = $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertRedirect('/login');
        $this->assertFalse(Auth::check());
    }

    public function test_akun_aktif_tetap_bisa_login(): void
    {
        $user = User::factory()->create(['role' => 'admin_default', 'password' => bcrypt('password')]);

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertRedirect($user->dashboardUrl());
        $this->assertTrue(Auth::check());
    }

    public function test_extras_tidak_aktif_tetap_bisa_login(): void
    {
        $user = User::factory()->create(['role' => 'extras', 'password' => bcrypt('password')]);
        $profile = ExtrasProfile::create(['user_id' => $user->id, 'alias' => 'Alias Test']);
        $profile->forceFill(['status' => 'tidak_aktif'])->save();

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertRedirect($user->dashboardUrl());
        $this->assertTrue(Auth::check());
    }
}
