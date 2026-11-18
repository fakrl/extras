<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RememberMeCookieDurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cookie_remember_me_bertahan_sekitar_30_hari(): void
    {
        $user = User::factory()->create([
            'role' => 'extras',
            'status' => 'aktif',
            'password' => bcrypt('rahasia123'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'rahasia123',
            'remember' => '1',
        ]);

        $recaller = collect($response->headers->getCookies())
            ->first(fn ($cookie) => str_contains($cookie->getName(), 'remember'));

        $this->assertNotNull($recaller, 'Cookie remember-me tidak ditemukan di response.');

        $hariTersisa = ($recaller->getExpiresTime() - now()->timestamp) / 86400;

        $this->assertGreaterThan(28.5, $hariTersisa);
        $this->assertLessThan(31.5, $hariTersisa);
    }
}
