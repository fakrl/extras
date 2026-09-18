<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\CdReview;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GreenlightGridTest extends TestCase
{
    use RefreshDatabase;

    private function buatProyek(User $admin): CastingProject
    {
        return CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Test Proyek',
            'client_ph' => 'PH Test',
            'deadline' => now()->addDays(10),
            'kuota' => 5,
        ]);
    }

    private function buatApplication(CastingProject $project, string $status = 'diajukan_ke_cd', string $username = 'alias_tes'): ProjectApplication
    {
        $extrasUser = User::factory()->create(['role' => 'extras', 'username' => $username]);
        $extras = ExtrasProfile::create([
            'user_id' => $extrasUser->id,
            'nama_asli' => 'Nama Asli Rahasia',
            'usia' => 28,
            'gender' => 'Pria',
            'rate_card' => 300000,
        ]);

        return ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => $status,
            'fee_final' => 200000,
        ]);
    }

    public function test_show_tidak_bocorkan_nama_asli_nik_rate_card(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $cd = User::factory()->create(['role' => 'casting_director']);
        $project = $this->buatProyek($admin);
        $project->cdAssignments()->create(['cd_user_id' => $cd->id]);
        $this->buatApplication($project, 'diajukan_ke_cd', 'alias_aman');

        $response = $this->actingAs($cd)->get(route('cd.reviews.show', $project));
        $response->assertOk();
        $response->assertDontSee('Nama Asli Rahasia');
        $response->assertDontSee('nama_asli');
        $response->assertDontSee('rate_card');
        $response->assertDontSee('rekening');
    }

    public function test_riwayat_cd_muncul_kalau_pernah_approve(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $cd = User::factory()->create(['role' => 'casting_director']);

        $project1 = $this->buatProyek($admin);
        $project1->cdAssignments()->create(['cd_user_id' => $cd->id]);

        $project2 = $this->buatProyek($admin);
        $project2->cdAssignments()->create(['cd_user_id' => $cd->id]);

        $app1 = $this->buatApplication($project1, 'diajukan_ke_cd', 'alias_riwayat');

        $app2 = ProjectApplication::create([
            'casting_project_id' => $project2->id,
            'extras_id' => $app1->extras_id,
            'status_partisipasi' => 'lolos',
            'fee_final' => 200000,
        ]);
        CdReview::create([
            'project_application_id' => $app2->id,
            'cd_id' => $cd->id,
            'keputusan' => 'approve',
            'grade_cd' => 'B',
        ]);

        $response = $this->actingAs($cd)->get(route('cd.reviews.show', $project1));
        $response->assertOk();
        $response->assertSee('riwayat-approve');
    }

    public function test_grade_cd_wajib_saat_approve(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $cd = User::factory()->create(['role' => 'casting_director']);
        $project = $this->buatProyek($admin);
        $project->cdAssignments()->create(['cd_user_id' => $cd->id]);
        $app = $this->buatApplication($project, 'diajukan_ke_cd');

        $response = $this->actingAs($cd)->post(route('cd.reviews.review'), [
            'application_ids' => [$app->id],
            'keputusan' => 'approve',
        ]);

        $response->assertSessionHasErrors(['grade_cd']);
    }

    public function test_bulk_reject_masih_jalan(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $cd = User::factory()->create(['role' => 'casting_director']);
        $project = $this->buatProyek($admin);
        $project->cdAssignments()->create(['cd_user_id' => $cd->id]);

        $app1 = $this->buatApplication($project, 'diajukan_ke_cd', 'alias_bulk1');
        $app2 = $this->buatApplication($project, 'diajukan_ke_cd', 'alias_bulk2');

        $response = $this->actingAs($cd)->post(route('cd.reviews.review'), [
            'application_ids' => [$app1->id, $app2->id],
            'keputusan' => 'reject',
        ]);

        $response->assertRedirect();
        $this->assertSame('ditolak', $app1->fresh()->status_partisipasi);
        $this->assertSame('ditolak', $app2->fresh()->status_partisipasi);
    }
}
