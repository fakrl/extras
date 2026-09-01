<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CastingProjectEditTest extends TestCase
{
    use RefreshDatabase;

    private function buatProyek(User $admin): CastingProject
    {
        $project = CastingProject::factory()->create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Lama',
            'client_ph' => 'PH Lama',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
            'status' => 'dibuka',
        ]);

        $project->shootingDates()->create(['tanggal' => now()->addDays(10)->toDateString()]);

        return $project;
    }

    private function payload(CastingProject $project, array $kelas, array $overrides = []): array
    {
        return array_merge([
            'nama_produksi' => 'Proyek Baru',
            'client_ph' => 'PH Baru',
            'deadline' => now()->addDays(14)->toDateString(),
            'kuota' => 10,
            'is_urgent' => '1',
            'tanggal_shooting' => [now()->addDays(20)->toDateString()],
            'kelas' => $kelas,
        ], $overrides);
    }

    public function test_edit_update_semua_field(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $project = $this->buatProyek($admin);
        $kelasLama = $project->classes()->create(['nama_kelas' => 'Kelas Lama', 'budget_client' => 100000, 'kuota_kelas' => 2]);

        $response = $this->actingAs($admin)->patch(route('admin.projects.update', $project), $this->payload($project, [
            ['id' => $kelasLama->id, 'nama_kelas' => 'Kelas Baru', 'budget_client' => 250000, 'kuota_kelas' => 4],
        ]));

        $response->assertRedirect(route('admin.projects.index'));

        $project->refresh();
        $this->assertSame('Proyek Baru', $project->nama_produksi);
        $this->assertSame('PH Baru', $project->client_ph);
        $this->assertSame(10, $project->kuota);
        $this->assertTrue($project->is_urgent);
        $this->assertSame(now()->addDays(20)->toDateString(), $project->shootingDates()->first()->tanggal->toDateString());

        $kelasBaru = $project->classes()->first();
        $this->assertSame('Kelas Baru', $kelasBaru->nama_kelas);
        $this->assertEquals(250000, $kelasBaru->budget_client);
        $this->assertSame(4, $kelasBaru->kuota_kelas);
    }

    public function test_kelas_berpendaftar_tidak_bisa_dihapus(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $project = $this->buatProyek($admin);
        $kelasA = $project->classes()->create(['nama_kelas' => 'Kelas A', 'budget_client' => 100000, 'kuota_kelas' => 2]);
        $project->classes()->create(['nama_kelas' => 'Kelas B', 'budget_client' => 200000, 'kuota_kelas' => 2]);

        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);
        ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'diajukan',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.projects.update', $project), $this->payload($project, [
            ['id' => $kelasA->id, 'nama_kelas' => $kelasA->nama_kelas, 'budget_client' => $kelasA->budget_client, 'kuota_kelas' => $kelasA->kuota_kelas],
        ]));

        $response->assertSessionHasErrors('kelas');
        $this->assertSame(2, $project->classes()->count());
    }

    public function test_kelas_diupdate_in_place_saat_ada_pendaftar(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $project = $this->buatProyek($admin);
        $kelasA = $project->classes()->create(['nama_kelas' => 'Kelas A', 'budget_client' => 100000, 'kuota_kelas' => 2]);

        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);
        ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'diajukan',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.projects.update', $project), $this->payload($project, [
            ['id' => $kelasA->id, 'nama_kelas' => 'Kelas A Revisi', 'budget_client' => 500000, 'kuota_kelas' => 9],
        ]));

        $response->assertRedirect(route('admin.projects.index'));
        $this->assertDatabaseHas('casting_project_classes', [
            'id' => $kelasA->id,
            'nama_kelas' => 'Kelas A Revisi',
            'budget_client' => 500000,
            'kuota_kelas' => 9,
        ]);
    }

    public function test_kelas_tanpa_pendaftar_bebas_dihapus(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $project = $this->buatProyek($admin);
        $project->classes()->create(['nama_kelas' => 'Kelas A', 'budget_client' => 100000, 'kuota_kelas' => 2]);
        $project->classes()->create(['nama_kelas' => 'Kelas B', 'budget_client' => 200000, 'kuota_kelas' => 2]);

        $response = $this->actingAs($admin)->patch(route('admin.projects.update', $project), $this->payload($project, [
            ['nama_kelas' => 'Kelas C', 'budget_client' => 300000, 'kuota_kelas' => 3],
        ]));

        $response->assertRedirect(route('admin.projects.index'));
        $this->assertSame(1, $project->classes()->count());
        $this->assertSame('Kelas C', $project->classes()->first()->nama_kelas);
    }

    public function test_kelas_baru_bisa_ditambah_walau_sudah_ada_pendaftar(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $project = $this->buatProyek($admin);
        $kelasA = $project->classes()->create(['nama_kelas' => 'Kelas A', 'budget_client' => 100000, 'kuota_kelas' => 2]);

        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);
        ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'diajukan',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.projects.update', $project), $this->payload($project, [
            ['id' => $kelasA->id, 'nama_kelas' => $kelasA->nama_kelas, 'budget_client' => $kelasA->budget_client, 'kuota_kelas' => $kelasA->kuota_kelas],
            ['nama_kelas' => 'Kelas Baru', 'budget_client' => 150000, 'kuota_kelas' => 1],
        ]));

        $response->assertRedirect(route('admin.projects.index'));
        $this->assertSame(2, $project->classes()->count());
        $this->assertDatabaseHas('casting_project_classes', ['id' => $kelasA->id, 'casting_project_id' => $project->id]);
    }

    public function test_edit_form_bisa_diakses(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $project = $this->buatProyek($admin);
        $project->classes()->create(['nama_kelas' => 'Kelas A', 'budget_client' => 100000, 'kuota_kelas' => 2]);

        $this->actingAs($admin)->get(route('admin.projects.edit', $project))
            ->assertOk()
            ->assertSee('Proyek Lama')
            ->assertSee('Kelas A');
    }
}
