<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** QA: "Lowongan: Dibuka" di admin/projects/show menyesatkan kalau sebenarnya tidak menerimaPendaftaran(). */
class LowonganAlasanTidakTerimaTest extends TestCase
{
    use RefreshDatabase;

    public function test_tampilkan_alasan_saat_deadline_sudah_lewat(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Deadline Lewat',
            'client_request_status' => 'disetujui',
            'status' => 'dibuka',
            'deadline' => today()->subDays(2),
            'kuota' => 5,
        ]);

        $this->actingAs($admin)->get(route('admin.projects.show', $project))
            ->assertOk()
            ->assertSee('Dibuka')
            ->assertSee('deadline sudah lewat');
    }

    public function test_tampilkan_alasan_saat_kuota_penuh(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Kuota Penuh',
            'client_request_status' => 'disetujui',
            'status' => 'dibuka',
            'deadline' => today()->addDays(5),
            'kuota' => 1,
        ]);
        $kelas = $project->classes()->create(['nama_kelas' => 'A', 'kuota_kelas' => 1]);
        ProjectApplication::create([
            'casting_project_id' => $project->id,
            'casting_project_class_id' => $kelas->id,
            'extras_id' => ExtrasProfile::factory()->create()->id,
            'status_partisipasi' => 'lolos',
        ]);

        $this->actingAs($admin)->get(route('admin.projects.show', $project))
            ->assertOk()
            ->assertSee('Dibuka')
            ->assertSee('kuota penuh');
    }

    public function test_tidak_ada_alasan_untuk_lowongan_normal_dibuka(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Normal Dibuka',
            'client_request_status' => 'disetujui',
            'status' => 'dibuka',
            'deadline' => today()->addDays(5),
            'kuota' => 5,
        ]);

        $this->actingAs($admin)->get(route('admin.projects.show', $project))
            ->assertOk()
            ->assertSee('Dibuka')
            ->assertDontSee('deadline sudah lewat')
            ->assertDontSee('kuota penuh')
            ->assertDontSee('client belum menyetujui');
    }
}
