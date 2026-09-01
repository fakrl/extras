<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * SPEC.md Bagian B4/B5/B6 (RF-56): link publik pendaftaran event + return-
 * to-intent lewat register/login. "Kuota penuh" = total applications proyek
 * vs kolom kuota level-proyek (bukan kuota_kelas), lihat CastingProject::kuotaPenuh().
 */
class PublicEventLinkTest extends TestCase
{
    use RefreshDatabase;

    private function buatProyek(array $overrides = []): CastingProject
    {
        $admin = User::factory()->create(['role' => 'admin_default']);

        return CastingProject::create(array_merge([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Event Test',
            'client_ph' => 'PH Rahasia Banget',
            'share_token' => Str::random(32),
            'deadline' => now()->addDays(7),
            'kuota' => 5,
            'status' => 'dibuka',
        ], $overrides));
    }

    private function buatExtras(string $password = 'password'): User
    {
        $user = User::factory()->create(['role' => 'extras', 'password' => bcrypt($password)]);
        ExtrasProfile::create(['user_id' => $user->id, 'alias' => 'Alias Test']);

        return $user;
    }

    // 1. Guest buka link event valid -> klik Daftar -> lengkapi profil -> auto redirect ke apply proyek yang benar.
    public function test_guest_daftar_dari_link_event_redirect_ke_apply_setelah_lengkapi_profil(): void
    {
        $project = $this->buatProyek();

        $this->get('/register?event='.$project->share_token)
            ->assertSessionHas('intended_event_token', $project->share_token);

        $this->post('/register', [
            'name' => 'Extras Baru',
            'email' => 'extras-baru@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'setuju_privasi' => '1',
        ])->assertRedirect('/extras/profil/lengkapi');

        $response = $this->put('/extras/profil', [
            'alias' => 'Alias Baru',
            'nama_asli' => 'Nama Asli',
            'username' => 'aliasbaru',
        ]);

        $response->assertRedirect(route('extras.projects.show', $project));
    }

    // 2. User existing (extras) buka link event valid, belum login -> klik Masuk -> login sukses -> redirect ke apply proyek yang benar.
    public function test_extras_existing_login_dari_link_event_redirect_ke_apply(): void
    {
        $project = $this->buatProyek();
        $extrasUser = $this->buatExtras();

        $this->get('/login?event='.$project->share_token)
            ->assertSessionHas('intended_event_token', $project->share_token);

        $response = $this->post('/login', [
            'email' => $extrasUser->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('extras.projects.show', $project));
    }

    // 3. User yang sudah login buka link event -> CTA langsung ke apply page, tidak lewat login/register.
    public function test_extras_sudah_login_buka_link_event_langsung_cta_apply(): void
    {
        $project = $this->buatProyek();
        $extrasUser = $this->buatExtras();

        $response = $this->actingAs($extrasUser)->get('/event/'.$project->share_token);

        $response->assertOk();
        $response->assertSee(route('extras.projects.show', $project), false);
        $response->assertDontSee(route('register'), false);
    }

    // 4. Token tidak ditemukan -> halaman graceful, bukan 500/404 keras.
    public function test_token_tidak_ditemukan_tampil_halaman_graceful(): void
    {
        $response = $this->get('/event/token-tidak-ada-di-database');

        $response->assertOk();
        $response->assertSee('Pendaftaran sudah tidak dibuka');
    }

    // 5. Token valid tapi proyek closed/deadline lewat/kuota penuh -> halaman graceful.
    public function test_proyek_tidak_valid_tampil_halaman_graceful(): void
    {
        $extrasUser = $this->buatExtras();

        $ditutup = $this->buatProyek(['status' => 'ditutup']);
        $deadlineLewat = $this->buatProyek(['deadline' => now()->subDay()]);
        $kuotaPenuh = $this->buatProyek(['kuota' => 1]);
        $kuotaPenuh->applications()->create([
            'extras_id' => $extrasUser->extrasProfile->id,
            'status_partisipasi' => 'diajukan',
        ]);

        foreach ([$ditutup, $deadlineLewat, $kuotaPenuh] as $project) {
            $response = $this->get('/event/'.$project->share_token);
            $response->assertOk();
            $response->assertSee('Pendaftaran sudah tidak dibuka');
        }
    }

    // 6. client_ph & budget_client tidak pernah muncul di halaman /event/{token}.
    public function test_client_ph_dan_budget_client_tidak_tampil_di_halaman_event(): void
    {
        $project = $this->buatProyek(['client_ph' => 'Client Sangat Rahasia XYZ']);
        $project->classes()->create([
            'nama_kelas' => 'Ibu-ibu', 'budget_client' => 777777, 'kuota_kelas' => 3,
        ]);

        $response = $this->get('/event/'.$project->share_token);

        $response->assertOk();
        $response->assertDontSee('Client Sangat Rahasia XYZ');
        $response->assertDontSee('777777');
        $response->assertDontSee('777.777');
    }

    // 7. Alur login/register NORMAL (tanpa buka link event dulu) tetap seperti sebelumnya.
    public function test_alur_register_dan_login_normal_tanpa_event_tetap_seperti_biasa(): void
    {
        $this->get('/register');

        $this->post('/register', [
            'name' => 'Extras Biasa',
            'email' => 'extras-biasa@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'setuju_privasi' => '1',
        ]);

        $response = $this->put('/extras/profil', [
            'alias' => 'Alias Biasa',
            'nama_asli' => 'Nama Biasa',
            'username' => 'aliasbiasa',
        ]);

        $response->assertRedirect('/extras/profil');

        Auth::logout();

        $admin = User::factory()->create(['role' => 'admin_default', 'password' => bcrypt('password')]);
        $this->get('/login');
        $loginResponse = $this->post('/login', ['email' => $admin->email, 'password' => 'password']);

        $loginResponse->assertRedirect($admin->dashboardUrl());
    }

    // 8. Homepage: modal cuma untuk guest, counter benar, CTA "Ke Dashboard" kalau sudah login.
    public function test_homepage_modal_counter_dan_cta_sesuai_status_login(): void
    {
        $this->buatProyek();
        $this->buatProyek(['status' => 'ditutup']);

        $guestResponse = $this->get('/');
        $guestResponse->assertOk();
        $guestResponse->assertSee('welcome-modal', false);
        $guestResponse->assertSee('1');
        $guestResponse->assertSee('Daftar Akun');

        $extrasUser = $this->buatExtras();
        $loggedInResponse = $this->actingAs($extrasUser)->get('/');
        $loggedInResponse->assertOk();
        $loggedInResponse->assertDontSee('welcome-modal', false);
        $loggedInResponse->assertSee('Ke Dashboard');
        $loggedInResponse->assertDontSee('Daftar Akun');
    }
}
