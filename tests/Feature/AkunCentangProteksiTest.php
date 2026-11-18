<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AkunCentangProteksiTest extends TestCase
{
    use RefreshDatabase;

    public function test_akun_terproteksi_dan_terhapus_tidak_punya_checkbox_tapi_ada_penjelasan(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $protected = User::factory()->create(['role' => 'admin', 'name' => 'Admin Proteksi', 'is_protected' => true]);
        $normal = User::factory()->create(['role' => 'admin', 'name' => 'Admin Biasa']);

        $this->actingAs($sa)->get(route('super-admin.akun.index'))
            ->assertOk()
            ->assertSee('name="user_ids[]" value="'.$normal->id.'"', false)
            ->assertDontSee('name="user_ids[]" value="'.$protected->id.'"', false)
            ->assertSee('Akun terproteksi, tidak bisa dicentang untuk aksi massal');

        $terhapus = User::factory()->create(['role' => 'admin', 'name' => 'Admin Terhapus']);
        $terhapus->delete();

        $this->actingAs($sa)->get(route('super-admin.akun.index', ['status' => 'dihapus']))
            ->assertOk()
            ->assertDontSee('name="user_ids[]" value="'.$terhapus->id.'"', false)
            ->assertSee('Akun dihapus, tidak bisa dicentang untuk aksi massal');
    }
}
