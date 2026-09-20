<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * RF-57: halaman Casting Director dipisah dari halaman Admin, tapi
 * toggle/destroy tetap reuse route & controller yang sama (generic).
 */
class SuperAdminCdManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_cd_muncul_di_listing_admin_dengan_filter_cd(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $cd = User::factory()->create(['role' => 'casting_director', 'name' => 'CD Satu']);

        // Bagian AG: CD sekarang muncul di admin listing dengan filter role=casting_director
        $this->actingAs($superAdmin)
            ->get(route('super-admin.admins.index', ['role' => 'casting_director']))
            ->assertOk()
            ->assertSee('CD Satu');
    }

    public function test_cd_tidak_muncul_di_listing_admin_tanpa_filter(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $cd = User::factory()->create(['role' => 'casting_director', 'name' => 'CD Satu']);
        $admin = User::factory()->create(['role' => 'admin_default', 'name' => 'Admin Satu']);

        // Bagian AG: CD tidak muncul di listing default (hanya Admin roles)
        $this->actingAs($superAdmin)
            ->get(route('super-admin.admins.index'))
            ->assertOk()
            ->assertSee('Admin Satu')
            ->assertDontSee('CD Satu');
    }

    public function test_toggle_status_cd_lewat_route_generic_tetap_kerja(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $cd = User::factory()->create(['role' => 'casting_director', 'status' => 'aktif']);

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.admins.toggle-status', $cd))
            ->assertRedirect();

        $this->assertSame('nonaktif', $cd->fresh()->status);
    }

    public function test_soft_delete_cd_redirect_ke_listing_admin(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $cd = User::factory()->create(['role' => 'casting_director']);

        $this->actingAs($superAdmin)
            ->delete(route('super-admin.admins.destroy', $cd))
            ->assertRedirect();

        $this->assertNull(User::find($cd->id));
        $this->assertNotNull(User::withTrashed()->find($cd->id));
    }

    public function test_super_admin_bisa_lihat_detail_akun_cd(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $cd = User::factory()->create(['role' => 'casting_director', 'name' => 'CD Detail']);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.admins.show', $cd))
            ->assertOk()
            ->assertSee('CD Detail')
            ->assertSee(route('super-admin.admins.index'));
    }

    public function test_super_admin_bisa_bikin_akun_cd_baru(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)->post(route('super-admin.casting-directors.store'), [
            'name' => 'CD Baru',
            'email' => 'cd-baru@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('super-admin.admins.index', ['role' => 'casting_director']));

        $newCd = User::where('email', 'cd-baru@example.com')->first();
        $this->assertNotNull($newCd);
        $this->assertSame('casting_director', $newCd->role);
    }

    public static function bukanSuperAdminProviderCd(): array
    {
        return [
            ['admin_default'], ['admin_talco'], ['admin_korlap'], ['admin_sosmed'],
            ['casting_director'], ['extras'],
        ];
    }

    #[DataProvider('bukanSuperAdminProviderCd')]
    public function test_role_selain_super_admin_gagal_bikin_akun_cd(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $response = $this->actingAs($user)->post(route('super-admin.casting-directors.store'), [
            'name' => 'CD Ilegal',
            'email' => 'cd-ilegal@example.com',
            'password' => 'password123',
        ]);

        $response->assertForbidden();
        $this->assertTrue(User::where('email', 'cd-ilegal@example.com')->doesntExist());
    }

    public function test_email_duplikat_ditolak_saat_bikin_akun_cd(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        User::factory()->create(['role' => 'casting_director', 'email' => 'sudah-ada@example.com']);

        $response = $this->actingAs($superAdmin)->post(route('super-admin.casting-directors.store'), [
            'name' => 'CD Duplikat',
            'email' => 'sudah-ada@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }
}
