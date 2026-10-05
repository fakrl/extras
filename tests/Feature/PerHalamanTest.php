<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use App\Support\PerHalaman;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PerHalamanTest extends TestCase
{
    use RefreshDatabase;

    public function test_helper_hanya_terima_pilihan(): void
    {
        $dari = fn ($per) => PerHalaman::dari(Request::create('/', 'GET', ['per' => $per]), PerHalaman::TABEL);

        $this->assertSame(50, $dari('50'));
        $this->assertSame(10, $dari('999'));
        $this->assertSame(10, $dari('abc'));
        $this->assertSame(10, $dari(['50']));
        $this->assertSame(10, PerHalaman::dari(Request::create('/'), PerHalaman::TABEL));
    }

    public function test_tabel_per_50_dan_halaman_2_bawa_per(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        User::factory()->count(60)->create(['role' => 'client']);
        foreach (range(1, 60) as $i) {
            ActivityLog::create(['role' => 'admin', 'action' => 'X', 'description' => "Log {$i}", 'created_at' => now()->subMinutes($i)]);
        }

        foreach ([
            ['super-admin.akun.index', [], 'users'],
            ['super-admin.activity-logs', [], 'logs'],
            ['super-admin.mode.pilih', ['mode' => 'client'], 'akun'],
        ] as [$route, $param, $var]) {
            $r = $this->actingAs($sa)->get(route($route, $param + ['per' => 50]))->assertOk();
            $this->assertSame(50, $r->viewData($var)->perPage(), $route);
            $this->assertCount(50, $r->viewData($var)->items(), $route);
            $this->assertStringContainsString('per=50', $r->viewData($var)->url(2), $route);
            $r->assertSee('Menampilkan 1–50 dari', false)->assertSee('name="per"', false)->assertSee('form="live-form"', false);

            $this->assertSame(10, $this->get(route($route, $param + ['per' => 999]))->viewData($var)->perPage(), $route);
        }
    }

    public function test_grid_kartu_pakai_12_24_48_96_default_12(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::factory()->create(['admin_id' => $admin->id]);
        foreach (range(1, 50) as $i) {
            $e = ExtrasProfile::create(['user_id' => User::factory()->create(['role' => 'extras'])->id]);
            ProjectApplication::create(['casting_project_id' => $project->id, 'extras_id' => $e->id, 'status_partisipasi' => 'diajukan']);
        }
        CastingProject::factory()->count(30)->create(['admin_id' => $admin->id]);

        $akun = $this->actingAs($sa)->get(route('super-admin.akun.index', ['role' => 'extras', 'per' => 48]));
        $this->assertSame(48, $akun->viewData('users')->perPage());
        $this->assertSame(12, $this->get(route('super-admin.akun.index', ['role' => 'extras', 'per' => 50]))->viewData('users')->perPage());

        foreach ([
            [route('admin.projects.applicants', $project), 'applicants'],
            [route('admin.projects.index'), 'projects'],
        ] as [$url, $var]) {
            $this->assertSame(12, $this->actingAs($admin)->get($url)->viewData($var)->perPage(), $url);
            $this->assertSame(12, $this->get($url.'?per=25')->viewData($var)->perPage(), $url);
            $r = $this->get($url.'?per=12&page=2')->assertOk();
            $this->assertSame(12, $r->viewData($var)->perPage());
            $this->assertStringContainsString('per=12', $r->viewData($var)->url(3));
            $r->assertSee('Menampilkan 13–24 dari', false);
        }
    }
}
