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

    public function test_casting_director_registration_gets_casting_director_role(): void
    {
        $this->post('/register/casting-director', [
            'name' => 'Test CD',
            'email' => 'cd@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'setuju_privasi' => '1',
        ]);

        $user = User::where('email', 'cd@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('casting_director', $user->role);
    }
}
