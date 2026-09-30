<?php

namespace Tests\Feature;

use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BjBottomNavExtrasTest extends TestCase
{
    use RefreshDatabase;

    public function test_urutan_casting_call_beranda_profil_dan_beranda_ditonjolkan(): void
    {
        $u = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $u->id, 'usia' => 25, 'gender' => 'Wanita', 'tinggi_badan' => 160]);

        $html = $this->actingAs($u)->get(route('extras.dashboard'))->assertOk()
            ->assertSeeInOrder(['/extras/lowongan', '/extras/dashboard', '/extras/profil'], false)
            ->getContent();

        $this->assertMatchesRegularExpression('#href="[^"]*/extras/dashboard" class="sidebar-link is-utama active" aria-label="Beranda">\s*<i class="ti ti-home"></i> Beranda#', $html);
        $this->assertStringNotContainsString('ti-layout-dashboard', $html);
    }

    public function test_login_extras_tetap_ke_dashboard(): void
    {
        $this->assertSame('/extras/dashboard', User::factory()->make(['role' => 'extras'])->dashboardUrl());
    }
}
