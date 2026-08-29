<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CastingProjectApplyTest extends TestCase
{
    use RefreshDatabase;

    private function buatProyekDenganKelas(string $namaProduksi = 'Proyek Test'): CastingProject
    {
        $admin = User::factory()->create(['role' => 'admin_default']);

        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => $namaProduksi,
            'client_ph' => 'PH Test',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ]);

        $project->classes()->create([
            'nama_kelas' => 'Ibu-ibu',
            'budget_client' => 400000,
            'kuota_kelas' => 5,
        ]);

        return $project;
    }

    private function buatExtras(): User
    {
        $extrasUser = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);

        return $extrasUser;
    }

    public function test_apply_dengan_kelas_milik_proyek_ini_berhasil(): void
    {
        $project = $this->buatProyekDenganKelas();
        $kelas = $project->classes()->first();
        $extrasUser = $this->buatExtras();

        $response = $this->actingAs($extrasUser)->post("/extras/lowongan/{$project->id}/daftar", [
            'casting_project_class_id' => $kelas->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('project_applications', [
            'casting_project_id' => $project->id,
            'casting_project_class_id' => $kelas->id,
        ]);
    }

    public function test_apply_dengan_kelas_milik_proyek_lain_ditolak(): void
    {
        $project = $this->buatProyekDenganKelas('Proyek A');
        $projectLain = $this->buatProyekDenganKelas('Proyek B');
        $kelasProyekLain = $projectLain->classes()->first();
        $extrasUser = $this->buatExtras();

        $response = $this->actingAs($extrasUser)->post("/extras/lowongan/{$project->id}/daftar", [
            'casting_project_class_id' => $kelasProyekLain->id,
        ]);

        $response->assertNotFound();
        $this->assertDatabaseMissing('project_applications', [
            'casting_project_id' => $project->id,
        ]);
    }

    public function test_apply_tanpa_pilih_kelas_padahal_proyek_punya_kelas_ditolak(): void
    {
        $project = $this->buatProyekDenganKelas();
        $extrasUser = $this->buatExtras();

        $response = $this->actingAs($extrasUser)->post("/extras/lowongan/{$project->id}/daftar");

        $response->assertSessionHasErrors('casting_project_class_id');
        $this->assertDatabaseMissing('project_applications', [
            'casting_project_id' => $project->id,
        ]);
    }
}
