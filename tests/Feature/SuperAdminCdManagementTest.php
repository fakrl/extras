<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RF-57: halaman Casting Director dipisah dari halaman Admin, tapi
 * toggle/destroy tetap reuse route & controller yang sama (generic).
 */
class SuperAdminCdManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_cd_muncul_di_listing_cd_tidak_di_listing_admin(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $cd = User::factory()->create(['role' => 'casting_director', 'name' => 'CD Satu']);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.casting-directors.index'))
            ->assertOk()
            ->assertSee('CD Satu');

        $this->actingAs($superAdmin)
            ->get(route('super-admin.admins.index'))
            ->assertOk()
            ->assertDontSee('CD Satu');
    }

    public function test_admin_dan_super_admin_muncul_di_listing_admin_tidak_di_listing_cd(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin_default', 'name' => 'Admin Satu']);
        $otherSuperAdmin = User::factory()->create(['role' => 'super_admin', 'name' => 'SA Dua']);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.admins.index'))
            ->assertOk()
            ->assertSee('Admin Satu')
            ->assertSee('SA Dua');

        $this->actingAs($superAdmin)
            ->get(route('super-admin.casting-directors.index'))
            ->assertOk()
            ->assertDontSee('Admin Satu')
            ->assertDontSee('SA Dua');
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

    public function test_hapus_cd_bersih_redirect_ke_listing_cd(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $cd = User::factory()->create(['role' => 'casting_director']);

        $this->actingAs($superAdmin)
            ->delete(route('super-admin.admins.destroy', $cd))
            ->assertRedirect(route('super-admin.casting-directors.index'));

        $this->assertNull(User::find($cd->id));
    }
}
