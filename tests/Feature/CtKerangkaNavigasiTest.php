<?php

namespace Tests\Feature;

use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CT.3 + CT.4: Uji kerangka navigasi M3 dan kepadatan compact.
 */
class CtKerangkaNavigasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dan_super_admin_punya_density_compact(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $sa = User::factory()->create(['role' => 'super_admin', 'status' => 'aktif']);

        $htmlAdmin = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<body\b[^>]*data-density="compact"/', $htmlAdmin);

        $htmlSa = $this->actingAs($sa)->get(route('super-admin.dashboard'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<body\b[^>]*data-density="compact"/', $htmlSa);
    }

    public function test_role_lain_tanpa_density_compact(): void
    {
        $extras = User::factory()->create(['role' => 'extras', 'status' => 'aktif']);
        ExtrasProfile::create(['user_id' => $extras->id, 'usia' => 25, 'gender' => 'Wanita', 'tinggi_badan' => 160]);

        $htmlExtras = $this->actingAs($extras)->get(route('extras.dashboard'))->assertOk()->getContent();
        $this->assertDoesNotMatchRegularExpression('/<body\b[^>]*data-density="compact"/', $htmlExtras);

        $client = User::factory()->create(['role' => 'client', 'status' => 'aktif']);
        $htmlClient = $this->actingAs($client)->get(route('client.dashboard'))->assertOk()->getContent();
        $this->assertDoesNotMatchRegularExpression('/<body\b[^>]*data-density="compact"/', $htmlClient);

        $korlap = User::factory()->create(['role' => 'korlap', 'status' => 'aktif']);
        $htmlKorlap = $this->actingAs($korlap)->get(route('admin.dashboard'))->assertOk()->getContent();
        $this->assertDoesNotMatchRegularExpression('/<body\b[^>]*data-density="compact"/', $htmlKorlap);
    }

    public function test_korlap_punya_penanda_data_role(): void
    {
        $korlap = User::factory()->create(['role' => 'korlap', 'status' => 'aktif']);
        $htmlKorlap = $this->actingAs($korlap)->get(route('admin.dashboard'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<body\b[^>]*data-role="korlap"/', $htmlKorlap);
    }

    public function test_sa_mode_banner_bergaya_m3(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin', 'status' => 'aktif']);
        $html = $this->actingAs($sa)->withSession(['sa_mode' => 'admin'])
            ->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('class="sa-mode-banner"', $html);
    }
}
