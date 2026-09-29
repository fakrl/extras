<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Live search AJAX: halaman search server-side punya form[data-live] + [data-live-target]
 * (script global di layout mengganti area hasil tanpa reload).
 */
class LiveSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_search_server_side_siap_live_search(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        User::factory()->create(['role' => 'client', 'name' => 'Andini Cari']);

        foreach ([
            route('super-admin.akun.index', ['q' => 'Andini']),
            route('super-admin.activity-logs'),
            route('admin.projects.index'),
            route('super-admin.mode.pilih', 'client'),
        ] as $url) {
            $this->actingAs($sa)->get($url)->assertOk()
                ->assertSee('data-live>', false)
                ->assertSee('data-live-target', false);
        }

        $this->actingAs($sa)->get(route('super-admin.akun.index', ['q' => 'Andini']))->assertSee('Andini Cari');
    }
}
