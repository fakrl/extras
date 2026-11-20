<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dock bawah baru (Extras & Korlap, mobile <=768px) di layouts/app.blade.php, terpisah
 * dari dock landing lama (RebrandSicastiTest). Admin/Super Admin/Client tidak boleh berubah.
 */
class AppDockTest extends TestCase
{
    use RefreshDatabase;

    public function test_extras_punya_dock_dengan_item_sesuai_config(): void
    {
        $user = User::factory()->create(['role' => 'extras']);
        $html = $this->actingAs($user)->get(route('extras.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('class="bottom-dock"', $html);
        foreach (['Beranda', 'Lowongan', 'Profil', 'Menu'] as $label) {
            $this->assertStringContainsString($label, $html);
        }

        foreach (config('app-dock.extras.items') as $item) {
            $this->actingAs($user)->get(route($item['route']))->assertOk();
        }
    }

    public function test_korlap_punya_dock_dengan_item_sesuai_config(): void
    {
        $user = User::factory()->create(['role' => 'korlap']);
        $html = $this->actingAs($user)->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('class="bottom-dock"', $html);
        foreach (['Dashboard', 'Absensi', 'Honor', 'Menu'] as $label) {
            $this->assertStringContainsString($label, $html);
        }

        foreach (config('app-dock.korlap.items') as $item) {
            $this->actingAs($user)->get(route($item['route']))->assertOk();
        }
    }

    public function test_sheet_dock_berisi_keluar(): void
    {
        $user = User::factory()->create(['role' => 'extras']);
        $html = $this->actingAs($user)->get(route('extras.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('Keluar', $html);
        $this->assertStringContainsString('action="'.route('logout').'"', $html);
    }

    public function test_admin_super_admin_client_tidak_punya_dock(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->assertStringNotContainsString('class="bottom-dock"', $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent());

        $sa = User::factory()->create(['role' => 'super_admin']);
        $this->assertStringNotContainsString('class="bottom-dock"', $this->actingAs($sa)->get(route('super-admin.dashboard'))->assertOk()->getContent());

        $client = User::factory()->create(['role' => 'client']);
        $this->assertStringNotContainsString('class="bottom-dock"', $this->actingAs($client)->get(route('client.dashboard'))->assertOk()->getContent());
    }
}
