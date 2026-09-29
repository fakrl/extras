<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SPEC BD.8: sidebar Super Admin final + route lama redirect (bukan 404).
 */
class BdSidebarFinalTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_super_admin_final(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($sa)->get(route('super-admin.dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Dashboard', 'Manajemen Akun', 'Proyek &amp; Keuangan', 'Log Aktivitas', 'Monitoring'], false)
            ->assertSee(route('super-admin.akun.index'), false)
            ->assertSee(route('super-admin.mode.pilih', 'korlap'), false)
            ->assertDontSee('Monitoring Akun')
            ->assertDontSee('Kelola Akun');
    }

    public function test_route_lama_redirect_bukan_404(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);

        foreach (['/super-admin/monitoring', '/super-admin/admins?role=client', '/super-admin/casting-directors', '/super-admin/rekap-margin', '/admin/rekap-margin'] as $url) {
            $this->actingAs($sa)->get($url)->assertRedirect();
        }
    }
}
