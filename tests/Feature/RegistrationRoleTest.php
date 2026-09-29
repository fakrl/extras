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
            'name' => 'Test CD',
            'email' => 'cd@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'setuju_privasi' => '1',
        ])->assertRedirect(route('login'))->assertSessionHas('status');

        $this->assertDatabaseMissing('users', ['email' => 'cd@example.com']);
    }
}
