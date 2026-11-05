<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_extras_registration_gets_extras_role_not_default_enum_value(): void
    {
        $this->post('/register', [
            'name' => 'Test Extras',
            'email' => 'extras@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'setuju_privasi' => '1',
        ]);

        $user = User::where('email', 'extras@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('extras', $user->role);
    }

    public function test_registrasi_publik_client_ditutup(): void
    {
        $this->get('/register/casting-director')->assertRedirect(route('login'));

        $this->post('/register/casting-director', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'setuju_privasi' => '1',
        ])->assertRedirect(route('login'))->assertSessionHas('status');

        $this->assertDatabaseMissing('users', ['email' => 'client@example.com']);
    }

    /** SPEC.md Bagian CY.3b: centang persetujuan sekarang mewakili Privasi + S&K sekaligus, field tetap sama. */
    public function test_registrasi_extras_ditolak_tanpa_centang_privasi(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test Extras',
            'email' => 'extras-nocheck@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('setuju_privasi');
        $this->assertDatabaseMissing('users', ['email' => 'extras-nocheck@example.com']);
    }

    public function test_halaman_registrasi_extras_tautkan_privasi_dan_syarat_ketentuan(): void
    {
        $response = $this->get('/register');

        $response->assertOk();
        $response->assertSee(route('privacy-policy'), false);
        $response->assertSee(route('syarat-ketentuan'), false);
    }
}
