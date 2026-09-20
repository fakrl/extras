<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_extras_hitting_admin_dashboard_gets_403(): void
    {
        $extras = User::factory()->create(['role' => 'extras']);

        $response = $this->actingAs($extras)->get('/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_extras_hitting_admin_dashboard_is_not_redirected_to_login(): void
    {
        $extras = User::factory()->create(['role' => 'extras']);

        $response = $this->actingAs($extras)->get('/admin/dashboard');

        $response->assertStatus(403);
        $this->assertNotEquals(302, $response->getStatusCode());
    }

    public function test_guest_hitting_admin_dashboard_is_redirected_to_login(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_super_admin_has_godmode_access_to_admin_and_korlap_routes(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        // Akses rute admin
        $this->actingAs($superAdmin)->get('/admin/dashboard')->assertOk();

        // Akses rute absensi korlap
        $this->actingAs($superAdmin)->get(route('admin.attendance.index'))->assertOk();
    }

    public function test_client_cannot_access_admin_dashboard(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        $this->actingAs($client)->get('/admin/dashboard')->assertStatus(403);
    }
}
