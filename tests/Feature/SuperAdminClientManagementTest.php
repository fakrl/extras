<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * RF-57: halaman Client dipisah dari halaman Admin, tapi
 * toggle/destroy tetap reuse route & controller yang sama (generic).
 */
class SuperAdminClientManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_muncul_di_listing_admin_dengan_filter_client(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $klien = User::factory()->create(['role' => 'client', 'name' => 'Client Satu']);

        // Bagian AG: Client sekarang muncul di admin listing dengan filter role=client
        $this->actingAs($superAdmin)
            ->get(route('super-admin.akun.index', ['role' => 'client']))
            ->assertOk()
            ->assertSee('Client Satu');
    }

    public function test_listing_default_menampilkan_semua_role_dan_filter_role_menyaring(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        User::factory()->create(['role' => 'client', 'name' => 'Client Satu']);
        User::factory()->create(['role' => 'admin', 'name' => 'Admin Satu']);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.akun.index'))
            ->assertOk()
            ->assertSee('Admin Satu')
            ->assertSee('Client Satu');

        $this->actingAs($superAdmin)
            ->get(route('super-admin.akun.index', ['role' => 'admin']))
            ->assertSee('Admin Satu')
            ->assertDontSee('Client Satu');
    }

    public function test_toggle_status_client_lewat_route_generic_tetap_kerja(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $klien = User::factory()->create(['role' => 'client', 'status' => 'aktif']);

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.admins.toggle-status', $klien))
            ->assertRedirect();

        $this->assertSame('nonaktif', $klien->fresh()->status);
    }

    public function test_soft_delete_client_redirect_ke_listing_admin(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $klien = User::factory()->create(['role' => 'client']);

        $this->actingAs($superAdmin)
            ->delete(route('super-admin.admins.destroy', $klien))
            ->assertRedirect();

        $this->assertNull(User::find($klien->id));
        $this->assertNotNull(User::withTrashed()->find($klien->id));
    }

    public function test_super_admin_bisa_lihat_detail_akun_client(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $klien = User::factory()->create(['role' => 'client', 'name' => 'Client Detail']);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.admins.show', $klien))
            ->assertOk()
            ->assertSee('Client Detail')
            ->assertSee(route('super-admin.akun.index'));
    }

    public function test_super_admin_bisa_bikin_akun_client_baru(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)->from(route('super-admin.akun.index'))->post(route('super-admin.clients.store'), [
            'name' => 'Client Baru',
            'email' => 'client-baru@example.com',
            'username' => 'client_baru',
        ]);

        $response->assertRedirect(route('super-admin.akun.index'));

        $newClient = User::where('email', 'client-baru@example.com')->first();
        $this->assertNotNull($newClient);
        $this->assertTrue($newClient->isClient());
    }

    public static function bukanSuperAdminProviderClient(): array
    {
        return [
            ['admin'], ['korlap'], ['client'], ['extras'],
        ];
    }

    #[DataProvider('bukanSuperAdminProviderClient')]
    public function test_role_selain_super_admin_gagal_bikin_akun_client(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $response = $this->actingAs($user)->post(route('super-admin.clients.store'), [
            'name' => 'Client Ilegal',
            'email' => 'client-ilegal@example.com',
            'username' => 'client_ilegal',
        ]);

        $response->assertForbidden();
        $this->assertTrue(User::where('email', 'client-ilegal@example.com')->doesntExist());
    }

    public function test_email_duplikat_ditolak_saat_bikin_akun_client(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        User::factory()->create(['role' => 'client', 'email' => 'sudah-ada@example.com']);

        $response = $this->actingAs($superAdmin)->post(route('super-admin.clients.store'), [
            'name' => 'Client Duplikat',
            'email' => 'sudah-ada@example.com',
            'username' => 'client_duplikat',
        ]);

        $response->assertSessionHasErrors('email');
    }
}
