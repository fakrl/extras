<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
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

    public function test_search_lineup_server_side_lintas_halaman(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::factory()->create(['admin_id' => $admin->id]);
        foreach (range(1, 31) as $i) {
            $e = ExtrasProfile::create(['user_id' => User::factory()->create(['role' => 'extras', 'username' => 'isi_'.$i])->id]);
            ProjectApplication::create(['casting_project_id' => $project->id, 'extras_id' => $e->id, 'status_partisipasi' => 'diajukan']);
        }
        $target = ExtrasProfile::create(['user_id' => User::factory()->create(['role' => 'extras', 'username' => 'dicari_banget'])->id]);
        ProjectApplication::create(['casting_project_id' => $project->id, 'extras_id' => $target->id, 'status_partisipasi' => 'diajukan']);

        $res = $this->actingAs($admin)->get(route('admin.projects.applicants', [$project, 'q' => 'dicari_banget']))->assertOk();
        $this->assertSame(1, $res->viewData('applicants')->total());
        $res->assertSee('data-live-target', false);
    }
}
