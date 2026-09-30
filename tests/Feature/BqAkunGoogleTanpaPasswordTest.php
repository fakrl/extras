<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** SPEC BQ.3: akun Google tanpa password (BO.2). */
class BqAkunGoogleTanpaPasswordTest extends TestCase
{
    use RefreshDatabase;

    private User $google;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google.client_id' => 'id-tes', 'services.google.client_secret' => 'rahasia']);
        $this->google = User::factory()->create(['role' => 'extras', 'username' => 'budi_g', 'email' => 'budi@gmail.com', 'password' => null]);
        $this->google->forceFill(['google_id' => 'g-1'])->save();
    }

    public function test_login_password_akun_google_dapat_pesan_khusus_bukan_error(): void
    {
        foreach (['budi_g', 'budi@gmail.com'] as $id) {
            $this->from('/login')->post('/login', ['email' => $id, 'password' => 'apa-saja'])
                ->assertRedirect('/login')->assertSessionHasErrors(['email' => 'Akun ini login pakai Google. Klik "Lanjut dengan Google", atau pakai "Lupa password" untuk membuat password.']);
            $this->assertGuest();
        }

        User::factory()->create(['role' => 'extras', 'username' => 'biasa']);
        $this->from('/login')->post('/login', ['email' => 'tidak_ada', 'password' => 'x'])->assertSessionHasErrors(['email' => 'Email/username atau password salah.']);
        $this->from('/login')->post('/login', ['email' => 'biasa', 'password' => 'salah'])->assertSessionHasErrors(['email' => 'Email/username atau password salah.']);
    }

    public function test_lupa_password_membuat_password_pertama_lalu_bisa_login(): void
    {
        Notification::fake();
        $this->post('/forgot-password', ['email' => 'budi_g'])->assertSessionHas('status');

        $token = null;
        Notification::assertSentTo($this->google, ResetPassword::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });

        $this->post('/reset-password', ['token' => $token, 'email' => 'budi@gmail.com', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123'])
            ->assertRedirect(route('login'))->assertSessionHasNoErrors();
        $this->assertNotNull($this->google->fresh()->password);

        $this->post('/login', ['email' => 'budi_g', 'password' => 'rahasia123'])->assertRedirect($this->google->dashboardUrl());
        $this->assertAuthenticatedAs($this->google);
    }

    public function test_putus_google_ditolak_selama_password_kosong(): void
    {
        $this->actingAs($this->google)->from('/ubah-password')->post(route('google.putus'))
            ->assertRedirect('/ubah-password')->assertSessionHas('error');
        $this->assertSame('g-1', $this->google->fresh()->google_id);
    }
}
