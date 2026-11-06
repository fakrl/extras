<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrustedProxiesTest extends TestCase
{
    use RefreshDatabase;

    private function registerProbeRoute(): void
    {
        Route::get('/__trust-proxy-probe', fn () => request()->isSecure() ? 'secure' : 'insecure');
    }

    // Pakai URL http:// eksplisit karena APP_URL di .env sudah https (ngrok) -
    // kalau uri relatif, url() helper bikin request test ikut https duluan
    // sebelum trust proxy sempat dicek, jadi tes jadi percuma.
    public function test_proxy_tepercaya_membuat_x_forwarded_proto_dipercaya(): void
    {
        config(['trustedproxy.proxies' => '127.0.0.1']);
        $this->registerProbeRoute();

        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('http://localhost/__trust-proxy-probe');

        $response->assertContent('secure');
    }

    public function test_proxy_tak_tepercaya_x_forwarded_proto_diabaikan(): void
    {
        config(['trustedproxy.proxies' => '127.0.0.1']);
        $this->registerProbeRoute();

        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.5'])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('http://localhost/__trust-proxy-probe');

        $response->assertContent('insecure');
    }

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
