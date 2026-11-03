<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrustedProxiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_throttle_login_pakai_ip_asli_bukan_x_forwarded_for_palsu(): void
    {
        User::factory()->create(['role' => 'extras', 'email' => 'korban@test.com']);

        for ($i = 1; $i <= 5; $i++) {
            $this->withHeaders(['X-Forwarded-For' => "203.0.113.$i"])
                ->post('/login', ['email' => 'korban@test.com', 'password' => 'salah'])
                ->assertSessionHasErrors();
        }

        // Percobaan ke-6, header X-Forwarded-For beda lagi - harus tetap kena
        // limit karena IP asli (koneksi test client) tidak berubah dan tidak
        // ada proxy tepercaya yang membuat header ini dipercaya.
        $this->withHeaders(['X-Forwarded-For' => '203.0.113.99'])
            ->post('/login', ['email' => 'korban@test.com', 'password' => 'salah'])
            ->assertStatus(429);
    }
}
