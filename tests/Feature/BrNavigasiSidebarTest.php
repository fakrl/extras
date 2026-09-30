<?php

namespace Tests\Feature;

use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BrNavigasiSidebarTest extends TestCase
{
    use RefreshDatabase;

    public static function roleNonExtras(): array
    {
        return [
            'admin' => ['admin', 'admin.dashboard', '/admin/projects'],
            'korlap' => ['korlap', 'admin.dashboard', '/admin/absensi'],
            'client' => ['client', 'client.dashboard', '/client/jadwal'],
            'super_admin' => ['super_admin', 'super-admin.dashboard', '/super-admin/activity-logs'],
        ];
    }

    #[DataProvider('roleNonExtras')]
    public function test_hp_non_extras_pakai_hamburger_drawer(string $role, string $dashboard, string $menu): void
    {
        $html = $this->actingAs(User::factory()->create(['role' => $role]))->get(route($dashboard))->assertOk()->getContent();

        $this->assertStringContainsString('class="app-shell nav-laci"', $html);
        $this->assertMatchesRegularExpression('#<aside class="sidebar" id="app-sidebar"[^>]*>(?:(?!</aside>).)*href="[^"]*'.preg_quote($menu, '#').'"#s', $html);
        $this->assertStringContainsString('id="nav-burger" aria-controls="app-sidebar" aria-expanded="false"', $html);
        $this->assertStringContainsString('data-nav-tutup', $html);
        $this->assertStringContainsString('id="sidebar-toggle" aria-controls="app-sidebar" aria-pressed="false"', $html);
    }

    public function test_extras_tetap_bottom_nav_tanpa_hamburger(): void
    {
        $u = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $u->id, 'usia' => 25, 'gender' => 'Wanita', 'tinggi_badan' => 160]);

        $this->actingAs($u)->get(route('extras.dashboard'))->assertOk()
            ->assertSee('class="app-shell nav-bawah"', false)
            ->assertSee('sidebar-link is-utama active', false)
            ->assertSee('id="sidebar-toggle"', false)
            ->assertDontSee('id="nav-burger"', false);
    }

    public function test_status_ringkas_dipasang_di_head_sebelum_render(): void
    {
        $html = $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('admin.dashboard'))->getContent();

        $this->assertLessThan(strpos($html, '</head>'), strpos($html, "localStorage.getItem('jbtb-sidebar')"));
    }

    public function test_sa_mode_korlap_drawer_berisi_menu_korlap(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'super_admin']))->withSession(['sa_mode' => 'korlap'])
            ->get(route('admin.attendance.index'))->assertOk()
            ->assertSee('id="nav-burger"', false)
            ->assertSee('Absensi Lapangan')
            ->assertDontSee('Log Aktivitas');
    }
}
