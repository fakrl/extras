<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** DA.2: consent privasi - migration, backfill, middleware, dan titik-titik yang mengisi timestamp. */
class PrivasiConsentTest extends TestCase
{
    use RefreshDatabase;

    public function test_extras_baru_diblokir_sampai_setuju_privasi(): void
    {
        $extras = User::factory()->create(['role' => 'extras', 'privasi_disetujui_at' => null]);

        $this->actingAs($extras)->get(route('extras.dashboard'))
            ->assertRedirect(route('privasi.consent'));
    }

    public function test_setuju_privasi_mengisi_timestamp_dan_lanjut_ke_dashboard(): void
    {
        $extras = User::factory()->create(['role' => 'extras', 'privasi_disetujui_at' => null]);

        $this->actingAs($extras)->post(route('privasi.consent.store'), ['setuju_privasi' => '1'])
            ->assertRedirect($extras->dashboardUrl());

        $this->assertNotNull($extras->fresh()->privasi_disetujui_at);

        $this->actingAs($extras->fresh())->get(route('extras.dashboard'))->assertOk();
    }

    public function test_tanpa_centang_setuju_ditolak_validasi(): void
    {
        $extras = User::factory()->create(['role' => 'extras', 'privasi_disetujui_at' => null]);

        $this->actingAs($extras)->post(route('privasi.consent.store'), [])
            ->assertSessionHasErrors('setuju_privasi');

        $this->assertNull($extras->fresh()->privasi_disetujui_at);
    }

    public function test_logout_tetap_bisa_diakses_walau_belum_setuju(): void
    {
        $extras = User::factory()->create(['role' => 'extras', 'privasi_disetujui_at' => null]);

        $this->actingAs($extras)->post(route('logout'))->assertRedirect();
    }

    public function test_halaman_legal_tetap_bisa_diakses_walau_belum_setuju(): void
    {
        $extras = User::factory()->create(['role' => 'extras', 'privasi_disetujui_at' => null]);

        $this->actingAs($extras)->get(route('privacy-policy'))->assertOk();
        $this->actingAs($extras)->get(route('syarat-ketentuan'))->assertOk();
    }

    public function test_role_selain_extras_tidak_kena_gerbang_consent(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'privasi_disetujui_at' => null]);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_register_mandiri_mengisi_timestamp_privasi(): void
    {
        $this->post(route('register'), [
            'name' => 'Extras Mandiri',
            'email' => 'mandiri@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'setuju_privasi' => '1',
        ]);

        $user = User::where('email', 'mandiri@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->privasi_disetujui_at);
    }

    public function test_google_daftar_mengisi_timestamp_privasi(): void
    {
        config(['services.google.client_id' => 'test-client-id']);

        $this->withSession(['google_baru' => [
            'id' => 'google-123',
            'name' => 'Google User',
            'email' => 'googleuser@example.com',
        ]])->post(route('google.daftar'), ['setuju_privasi' => '1']);

        $user = User::where('email', 'googleuser@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->privasi_disetujui_at);
    }

    public function test_backfill_tidak_memblokir_extras_lama(): void
    {
        $lama = User::factory()->create([
            'role' => 'extras',
            'privasi_disetujui_at' => null,
            'created_at' => now()->subDays(10),
        ]);

        $migration = require database_path('migrations/2026_11_24_000001_add_privasi_disetujui_at_to_users_table.php');
        $migration->up();

        $lama->refresh();
        $this->assertNotNull($lama->privasi_disetujui_at);
        $this->assertSame($lama->created_at->timestamp, $lama->privasi_disetujui_at->timestamp);

        $this->actingAs($lama)->get(route('extras.dashboard'))->assertOk();
    }
}
