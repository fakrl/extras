<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ClientReview;
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
            'deadline' => now()->addDays(10),
            'kuota' => 5,
        ]);
    }

    private function buatApplication(CastingProject $project, string $status = 'diajukan_ke_client', string $username = 'alias_tes'): ProjectApplication
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
        $admin = User::factory()->create(['role' => 'admin']);
        $klien = User::factory()->create(['role' => 'client']);
        $project = $this->buatProyek($admin);
        $project->update(['client_id' => $klien->id]);
        $this->buatApplication($project, 'diajukan_ke_client', 'alias_aman');

        $response = $this->actingAs($klien)->get(route('client.reviews.show', $project));
        $response->assertOk();
        $response->assertDontSee('Nama Asli Rahasia');
        $response->assertDontSee('nama_asli');
        $response->assertDontSee('rate_card');
        $response->assertDontSee('rekening');
    }

    public function test_riwayat_client_muncul_kalau_pernah_approve(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $klien = User::factory()->create(['role' => 'client']);

        $project1 = $this->buatProyek($admin);
        $project1->update(['client_id' => $klien->id]);

        $project2 = $this->buatProyek($admin);
        $project2->update(['client_id' => $klien->id]);

        $app1 = $this->buatApplication($project1, 'diajukan_ke_client', 'alias_riwayat');

        $app2 = ProjectApplication::create([
            'casting_project_id' => $project2->id,
            'extras_id' => $app1->extras_id,
            'status_partisipasi' => 'lolos',
            'fee_final' => 200000,
        ]);
        ClientReview::create([
            'project_application_id' => $app2->id,
            'client_id' => $klien->id,
            'keputusan' => 'approve',
            'grade_client' => 'B',
        ]);

        $response = $this->actingAs($klien)->get(route('client.reviews.show', $project1));
        $response->assertOk();
        $response->assertSee('riwayat-approve');
    }

    public function test_grade_client_wajib_saat_approve(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $klien = User::factory()->create(['role' => 'client']);
        $project = $this->buatProyek($admin);
        $project->update(['client_id' => $klien->id]);
        $app = $this->buatApplication($project, 'diajukan_ke_client');

        $response = $this->actingAs($klien)->post(route('client.reviews.review'), [
            'application_ids' => [$app->id],
            'keputusan' => 'approve',
        ]);

        $response->assertSessionHasErrors(['grade_client']);
    }

    public function test_bulk_reject_masih_jalan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $klien = User::factory()->create(['role' => 'client']);
        $project = $this->buatProyek($admin);
        $project->update(['client_id' => $klien->id]);

        $app1 = $this->buatApplication($project, 'diajukan_ke_client', 'alias_bulk1');
        $app2 = $this->buatApplication($project, 'diajukan_ke_client', 'alias_bulk2');

        $response = $this->actingAs($klien)->post(route('client.reviews.review'), [
            'application_ids' => [$app1->id, $app2->id],
            'keputusan' => 'reject',
        ]);

        $response->assertRedirect();
        $this->assertSame('ditolak', $app1->fresh()->status_partisipasi);
        $this->assertSame('ditolak', $app2->fresh()->status_partisipasi);
    }
}
