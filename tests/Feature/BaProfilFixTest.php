<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BaProfilFixTest extends TestCase
{
    use RefreshDatabase;

    public function test_ganti_role_ke_extras_membuat_profil(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $target = User::factory()->create(['role' => 'admin']);

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.admins.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'role' => 'extras',
            ])->assertRedirect();

        $this->assertNotNull($target->fresh()->extrasProfile);
    }

    public function test_profil_extras_tidak_ada_redirect_dengan_error(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $extras = User::factory()->create(['role' => 'extras']);

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->get(route('admin.extras.profil', $extras))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error');
    }

    public function test_akun_terhapus_tidak_ditautkan_ke_profil(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $extras = User::factory()->create(['role' => 'extras']);
        $extras->delete();

        $this->actingAs($superAdmin)
            ->get(route('super-admin.akun.index', ['role' => 'extras', 'status' => 'dihapus']))
            ->assertOk()
            ->assertSee('Dihapus')
            ->assertDontSee(route('admin.extras.profil', $extras));
    }
}
