<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    public function test_login_kena_rate_limit_pada_percobaan_keenam(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'x@x.com', 'password' => 'salah']);
        }

        $response = $this->post('/login', ['email' => 'x@x.com', 'password' => 'salah']);

        $response->assertStatus(429);
    }

    public function test_response_punya_security_headers(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Content-Security-Policy');
    }
}
