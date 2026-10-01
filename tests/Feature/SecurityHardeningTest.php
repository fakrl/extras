<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_kena_rate_limit_pada_percobaan_keenam(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'x@x.com', 'password' => 'salah']);
        }

        $response = $this->post('/login', ['email' => 'x@x.com', 'password' => 'salah']);

        $response->assertStatus(429);
    }

    public function test_reset_password_kena_rate_limit_pada_percobaan_keenam(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('password.update'), [
                'token' => 'token-salah',
                'email' => 'x@x.com',
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
            ]);
        }

        $response = $this->post(route('password.update'), [
            'token' => 'token-salah',
            'email' => 'x@x.com',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ]);

        $response->assertStatus(429);
    }

    public function test_halaman_404_tampil_untuk_url_ngaco(): void
    {
        $this->get('/url-ngaco-xyz')
            ->assertStatus(404)
            ->assertSee('Halaman Tidak Ditemukan');
    }

    public function test_response_punya_security_headers(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Content-Security-Policy');
    }

    public function test_hsts_tidak_muncul_di_environment_testing(): void
    {
        $response = $this->get('/login');

        $response->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_session_secure_cookie_tidak_dipaksa_true_di_environment_testing(): void
    {
        $this->get('/login');

        $this->assertNotTrue(config('session.secure'));
    }
}
