<?php

namespace Tests\Feature;

use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Bagian D3: satu input login menerima email ATAU username. Test pertama di
 * file ini adalah regresi login-email — kalau itu merah, fitur username
 * dianggap merusak pintu masuk utama sistem.
 */
class LoginUsernameTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_pakai_email_tetap_jalan(): void
    {
        $user = User::factory()->create([
            'role' => 'admin_default',
            'username' => 'admin_satu',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertRedirect($user->dashboardUrl());
        $this->assertTrue(Auth::check());
        $this->assertSame($user->id, Auth::id());
    }

    public function test_login_pakai_username_berhasil_sama_seperti_email(): void
    {
        $user = User::factory()->create([
            'role' => 'admin_default',
            'username' => 'admin_satu',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', ['email' => 'admin_satu', 'password' => 'password']);

        $response->assertRedirect($user->dashboardUrl());
        $this->assertTrue(Auth::check());
        $this->assertSame($user->id, Auth::id());
    }

    public function test_username_benar_password_salah_gagal(): void
    {
        User::factory()->create([
            'role' => 'admin_default',
            'username' => 'admin_satu',
            'password' => bcrypt('password'),
        ]);

        $this->from('/login')
            ->post('/login', ['email' => 'admin_satu', 'password' => 'salah'])
            ->assertRedirect('/login');

        $this->assertFalse(Auth::check());
    }

    public function test_username_tidak_terdaftar_gagal_tanpa_sesi(): void
    {
        $this->from('/login')
            ->post('/login', ['email' => 'tidak_ada', 'password' => 'password'])
            ->assertRedirect('/login');

        $this->assertFalse(Auth::check());
    }

    public function test_gate_akun_nonaktif_tetap_blokir_saat_login_via_username(): void
    {
        User::factory()->create([
            'role' => 'admin_default',
            'status' => 'nonaktif',
            'username' => 'admin_mati',
            'password' => bcrypt('password'),
        ]);

        $response = $this->from('/login')->post('/login', ['email' => 'admin_mati', 'password' => 'password']);

        $response->assertRedirect('/login');
        $this->assertFalse(Auth::check());
    }

    public function test_gate_extras_melanggar_tetap_blokir_saat_login_via_username(): void
    {
        $user = User::factory()->create([
            'role' => 'extras',
            'username' => 'extras_nakal',
            'password' => bcrypt('password'),
        ]);
        $profile = ExtrasProfile::create(['user_id' => $user->id, 'alias' => 'Alias Test']);
        $profile->forceFill(['status' => 'melanggar'])->save();

        $response = $this->from('/login')->post('/login', ['email' => 'extras_nakal', 'password' => 'password']);

        $response->assertRedirect('/login');
        $this->assertFalse(Auth::check());
    }

    public function test_extras_aktif_bisa_login_via_username(): void
    {
        $user = User::factory()->create([
            'role' => 'extras',
            'username' => 'extras_rajin',
            'password' => bcrypt('password'),
        ]);
        ExtrasProfile::create(['user_id' => $user->id, 'alias' => 'Alias Test']);

        $response = $this->post('/login', ['email' => 'extras_rajin', 'password' => 'password']);

        $response->assertRedirect($user->dashboardUrl());
        $this->assertTrue(Auth::check());
    }

    public function test_form_login_menampilkan_label_email_atau_username(): void
    {
        $this->get('/login')->assertSee('Email atau Username');
    }
}
