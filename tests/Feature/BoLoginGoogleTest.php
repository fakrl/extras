<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\GoogleController;
use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class BoLoginGoogleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google.client_id' => 'id-tes', 'services.google.client_secret' => 'rahasia']);
    }

    private function mockGoogle(string $id = 'g-1', string $email = 'baru@gmail.com', string $name = 'Budi Google'): void
    {
        $user = (new SocialiteUser)->map(['id' => $id, 'email' => $email, 'name' => $name]);
        Socialite::shouldReceive('driver->user')->andReturn($user);
    }

    public function test_config_kosong_tombol_hilang_dan_route_404(): void
    {
        config(['services.google.client_id' => null]);

        $this->get('/login')->assertOk()->assertDontSee('Lanjut dengan Google');
        $this->get('/register')->assertOk()->assertDontSee('Lanjut dengan Google');
        $this->get('/auth/google/redirect')->assertNotFound();
        $this->get('/auth/google/callback')->assertNotFound();
    }

    public function test_config_terisi_tombol_tampil_dan_redirect_ke_google(): void
    {
        $this->get('/login')->assertSee('Lanjut dengan Google');
        $this->get('/register')->assertSee('Lanjut dengan Google');

        Socialite::shouldReceive('driver->redirect')->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));
        $this->get('/auth/google/redirect?mode=login')->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    public function test_user_baru_wajib_setuju_privasi_lalu_akun_extras_dibuat(): void
    {
        $this->mockGoogle();

        $this->get('/auth/google/callback')->assertRedirect(route('google.lanjut'));
        $this->assertDatabaseMissing('users', ['email' => 'baru@gmail.com']);
        $this->get(route('google.lanjut'))->assertOk()->assertSee('baru@gmail.com');

        $this->post(route('google.daftar'))->assertSessionHasErrors('setuju_privasi');
        $this->assertGuest();

        $this->post(route('google.daftar'), ['setuju_privasi' => '1'])->assertRedirect('/extras/profil/lengkapi');

        $user = User::where('email', 'baru@gmail.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('extras', $user->role);
        $this->assertSame('g-1', $user->google_id);
        $this->assertSame('baru', $user->username);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->password);
        $this->assertNotNull($user->extrasProfile);
    }

    public function test_username_dibuat_unik(): void
    {
        User::factory()->create(['role' => 'extras', 'username' => 'baru']);
        $this->mockGoogle();

        $this->get('/auth/google/callback');
        $this->post(route('google.daftar'), ['setuju_privasi' => '1']);

        $this->assertSame('baru2', User::where('email', 'baru@gmail.com')->value('username'));
    }

    public function test_daftar_google_dari_link_event_tetap_return_to_intent(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'Event', 'share_token' => Str::random(32),
            'deadline' => now()->addDays(7), 'kuota' => 5, 'status' => 'dibuka',
        ]);
        $this->mockGoogle();

        $this->get('/register?event='.$project->share_token);
        $this->get('/auth/google/callback');
        $this->post(route('google.daftar'), ['setuju_privasi' => '1'])
            ->assertRedirect('/extras/profil/lengkapi')
            ->assertSessionHas('intended_event_token', $project->share_token);
    }

    public function test_extras_terdaftar_langsung_login_dan_ditautkan(): void
    {
        $extras = User::factory()->create(['role' => 'extras', 'email' => 'baru@gmail.com']);
        ExtrasProfile::create(['user_id' => $extras->id]);
        $this->mockGoogle();

        $this->get('/auth/google/callback')->assertRedirect('/extras/dashboard');
        $this->assertAuthenticatedAs($extras);
        $this->assertSame('g-1', $extras->fresh()->google_id);
    }

    public function test_client_belum_terhubung_ditolak_dengan_pesan_hubungkan(): void
    {
        $client = User::factory()->create(['role' => 'client', 'email' => 'baru@gmail.com']);
        $this->mockGoogle();

        $this->get('/auth/google/callback')->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => GoogleController::PESAN_BELUM_TERHUBUNG]);
        $this->assertGuest();
        $this->assertNull($client->fresh()->google_id);
    }

    public function test_client_sudah_terhubung_login_sukses(): void
    {
        $client = User::factory()->create(['role' => 'client', 'email' => 'lain@ph.test']);
        $client->forceFill(['google_id' => 'g-1'])->save();
        $this->mockGoogle();

        $this->get('/auth/google/callback')->assertRedirect('/client/dashboard');
        $this->assertAuthenticatedAs($client);
    }

    public function test_akun_nonaktif_ditolak(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'nonaktif']);
        $admin->forceFill(['google_id' => 'g-1'])->save();
        $this->mockGoogle();

        $this->get('/auth/google/callback')
            ->assertSessionHasErrors(['email' => 'Akun Anda dinonaktifkan. Hubungi admin untuk info lebih lanjut.']);
        $this->assertGuest();
    }

    public function test_hubungkan_google_dicatat_dan_isi_email_kosong(): void
    {
        $client = User::factory()->create(['role' => 'client', 'email' => null]);
        $this->actingAs($client);

        Socialite::shouldReceive('driver->redirect')->andReturn(redirect('https://accounts.google.com/x'));
        $this->from('/ubah-password')->get('/auth/google/redirect?mode=hubungkan')->assertRedirect('https://accounts.google.com/x');

        $this->mockGoogle('g-9', 'pic@gmail.com');
        $this->get('/auth/google/callback')->assertRedirect('/ubah-password')->assertSessionHas('status');

        $client->refresh();
        $this->assertSame('g-9', $client->google_id);
        $this->assertSame('pic@gmail.com', $client->email);
        $this->assertTrue(ActivityLog::where('action', 'HUBUNGKAN_GOOGLE')->where('user_id', $client->id)->exists());
    }

    public function test_mode_lihat_tidak_bisa_hubungkan(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $extras = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $extras->id]);

        $this->actingAs($sa)->withSession(['sa_mode' => 'extras', 'sa_view_user_id' => $extras->id])
            ->from('/extras/profil')->get('/auth/google/redirect?mode=hubungkan')
            ->assertRedirect('/extras/profil')->assertSessionHas('error');
        $this->assertNull($extras->fresh()->google_id);
        $this->assertNull($sa->fresh()->google_id);
    }

    public function test_putus_google_tanpa_password_ditolak(): void
    {
        $extras = User::factory()->create(['role' => 'extras', 'password' => null]);
        $extras->forceFill(['google_id' => 'g-1'])->save();

        $this->actingAs($extras)->from('/ubah-password')->post(route('google.putus'))
            ->assertRedirect('/ubah-password')->assertSessionHas('error');
        $this->assertSame('g-1', $extras->fresh()->google_id);
    }

    public function test_putus_google_dengan_password_berhasil_dan_dicatat(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->forceFill(['google_id' => 'g-1'])->save();

        $this->actingAs($admin)->get('/ubah-password')->assertSee('Putuskan Google');
        $this->post(route('google.putus'))->assertSessionHas('status');

        $this->assertNull($admin->fresh()->google_id);
        $this->assertTrue(ActivityLog::where('action', 'PUTUS_GOOGLE')->exists());
    }

    public function test_akun_tanpa_password_bisa_buat_password_pertama(): void
    {
        $extras = User::factory()->create(['role' => 'extras', 'password' => null]);

        $this->actingAs($extras)->post(route('ubah-password.update'), [
            'new_password' => 'rahasia123', 'new_password_confirmation' => 'rahasia123',
        ])->assertSessionHasNoErrors();

        $this->assertNotNull($extras->fresh()->password);
    }
}
