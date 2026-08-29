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
}
