<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** DA.1/DA.3/DA.4: Admin & Super Admin bikin akun Extras manual tanpa NIK/KTP. */
class CreateExtrasManualTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $override = []): array
    {
        return array_merge([
            'name' => 'Extras Baru',
            'username' => 'extras_baru',
            'nomor_wa' => '0812-3456-7890',
            'email' => 'extras-baru@example.com',
        ], $override);
    }

    public function test_admin_bisa_bikin_akun_extras_manual(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->from(route('admin.akun.extras'))
            ->post(route('admin.akun.extras.store'), $this->payload());

        $response->assertRedirect(route('admin.akun.extras'))->assertSessionHas('kredensial');

        $user = User::where('username', 'extras_baru')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->isExtras());
        $this->assertSame('aktif', $user->status);
        $this->assertTrue($user->wajib_ganti_password);
        $this->assertNull($user->privasi_disetujui_at);
    }

    public function test_super_admin_tanpa_sa_mode_ditolak(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($sa)->post(route('admin.akun.extras.store'), $this->payload());

        $response->assertForbidden();
        $this->assertTrue(User::where('username', 'extras_baru')->doesntExist());
    }

    public function test_super_admin_dengan_sa_mode_admin_bisa_bikin_akun_extras(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($sa)->withSession(['sa_mode' => 'admin'])
            ->post(route('admin.akun.extras.store'), $this->payload());

        $response->assertRedirect();
        $this->assertTrue(User::where('username', 'extras_baru')->exists());
    }

    public static function bukanAdminProvider(): array
    {
        return [['korlap'], ['client'], ['extras']];
    }

    #[DataProvider('bukanAdminProvider')]
    public function test_role_selain_admin_super_admin_ditolak(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $response = $this->actingAs($user)->post(route('admin.akun.extras.store'), $this->payload());

        $response->assertForbidden();
        $this->assertTrue(User::where('username', 'extras_baru')->doesntExist());
    }

    public function test_username_duplikat_ditolak(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'extras', 'username' => 'extras_baru']);

        $response = $this->actingAs($admin)->post(route('admin.akun.extras.store'), $this->payload());

        $response->assertSessionHasErrors('username');
    }

    public function test_email_duplikat_ditolak(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'extras', 'email' => 'extras-baru@example.com']);

        $response = $this->actingAs($admin)->post(route('admin.akun.extras.store'), $this->payload());

        $response->assertSessionHasErrors('email');
    }

    public function test_nomor_wa_wajib(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.akun.extras.store'), $this->payload(['nomor_wa' => '']));

        $response->assertSessionHasErrors('nomor_wa');
    }

    public function test_profil_dibuat_kosong_dan_tidak_tampil_di_beranda(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.akun.extras.store'), $this->payload());

        $user = User::where('username', 'extras_baru')->first();
        $profile = $user->extrasProfile;

        $this->assertNotNull($profile);
        $this->assertFalse($profile->izin_tampil_publik);
        $this->assertFalse($profile->tampil_di_beranda);
        $this->assertNull($profile->nik);
        $this->assertTrue(ExtrasProfile::tampilDiBeranda()->whereKey($profile->id)->doesntExist());
    }

    public function test_nomor_wa_dobel_tetap_lolos_dengan_peringatan_non_blokir(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'extras', 'nomor_wa' => '628123456790']);

        $response = $this->actingAs($admin)->post(route('admin.akun.extras.store'), $this->payload(['nomor_wa' => '0812-3456-790']));

        $response->assertRedirect()->assertSessionHas('info')->assertSessionHas('kredensial');
        $this->assertTrue(User::where('username', 'extras_baru')->exists());
    }

    /** DA.5: extras tanpa alur registrasi mandiri tetap bisa login pakai username (bukan cuma email). */
    public function test_extras_manual_bisa_login_pakai_username(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.akun.extras.store'), $this->payload(['email' => null]));
        $password = $response->getSession()->get('kredensial')['password'];

        auth()->logout();

        $this->post(route('login'), ['email' => 'extras_baru', 'password' => $password])
            ->assertRedirect();

        $this->assertAuthenticatedAs(User::where('username', 'extras_baru')->first());
    }

    /** DA.5: SA tetap bisa reset password akun Extras tanpa email (satu-satunya jalan selain lupa-password). */
    public function test_super_admin_bisa_reset_password_extras_tanpa_email(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $extras = User::factory()->create(['role' => 'extras', 'email' => null, 'username' => 'extras_tanpa_email']);

        $response = $this->actingAs($sa)->post(route('super-admin.admins.reset-password', $extras));

        $response->assertRedirect()->assertSessionHas('kredensial');
        $this->assertSame('extras_tanpa_email', $response->getSession()->get('kredensial')['username']);
        $this->assertTrue($extras->fresh()->wajib_ganti_password);
    }

    public function test_activity_log_tidak_memuat_password_sementara(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.akun.extras.store'), $this->payload());

        $password = $response->getSession()->get('kredensial')['password'];
        $user = User::where('username', 'extras_baru')->first();
        $log = ActivityLog::where('subject_id', $user->id)->where('subject_type', User::class)->where('action', 'CREATE_USER')->first();

        $this->assertNotNull($log);
        $this->assertStringNotContainsString($password, $log->description);
    }
}
