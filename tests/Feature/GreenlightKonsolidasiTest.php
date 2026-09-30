<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ClientReview;
use App\Models\ExtrasProfile;
use App\Models\Payment;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GreenlightKonsolidasiTest extends TestCase
{
    use RefreshDatabase;

    private function buatClient(): User
    {
        return User::factory()->create(['role' => 'client']);
    }

    private function buatProyek(User $admin): CastingProject
    {
        return CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Test',
            'deadline' => now()->addDays(7),
            'kuota' => 10,
        ]);
    }

    private function buatApplication(CastingProject $project, string $status = 'diajukan_ke_client'): ProjectApplication
    {
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);

        return ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => $status,
            'fee_final' => 150000,
        ]);
    }

    public function test_index_tampilkan_breakdown_count_per_proyek(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $klien = $this->buatClient();
        $project = $this->buatProyek($admin);
        $project->update(['client_id' => $klien->id]);

        $this->buatApplication($project, 'diajukan_ke_client');
        $this->buatApplication($project, 'lolos');
        $this->buatApplication($project, 'ditolak');

        $response = $this->actingAs($klien)->get(route('client.reviews.index'));
        $response->assertOk();

        $viewData = $response->original->getData();
        $item = $viewData['proyek']->first();

        $this->assertSame(1, $item['menunggu']);
        $this->assertSame(1, $item['approved']);
        $this->assertSame(1, $item['rejected']);
        $this->assertSame(3, $item['total']);
    }

    public function test_show_tampilkan_semua_status_bukan_hanya_pending(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $klien = $this->buatClient();
        $project = $this->buatProyek($admin);
        $project->update(['client_id' => $klien->id]);

        $appPending = $this->buatApplication($project, 'diajukan_ke_client');
        $appLolos = $this->buatApplication($project, 'lolos');
        $appTolak = $this->buatApplication($project, 'ditolak');

        $response = $this->actingAs($klien)->get(route('client.reviews.show', $project));
        $response->assertOk();

        $viewData = $response->original->getData();
        $ids = $viewData['applications']->pluck('id')->all();

        $this->assertContains($appPending->id, $ids);
        $this->assertContains($appLolos->id, $ids);
        $this->assertContains($appTolak->id, $ids);
    }

    public function test_show_filter_menunggu_hanya_tampilkan_diajukan_ke_client(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $klien = $this->buatClient();
        $project = $this->buatProyek($admin);
        $project->update(['client_id' => $klien->id]);

        $appPending = $this->buatApplication($project, 'diajukan_ke_client');
        $appLolos = $this->buatApplication($project, 'lolos');

        $response = $this->actingAs($klien)->get(route('client.reviews.show', ['castingProject' => $project, 'status' => 'menunggu']));
        $response->assertOk();

        $viewData = $response->original->getData();
        $ids = $viewData['applications']->pluck('id')->all();

        $this->assertContains($appPending->id, $ids);
        $this->assertNotContains($appLolos->id, $ids);
    }

    public function test_approve_dengan_grade_client_tersimpan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $klien = $this->buatClient();
        $project = $this->buatProyek($admin);
        $project->update(['client_id' => $klien->id]);

        $app = $this->buatApplication($project, 'diajukan_ke_client');

        $this->actingAs($klien)->post(route('client.reviews.review'), [
            'application_ids' => [$app->id],
            'keputusan' => 'approve',
            'grade_client' => 'B',
        ])->assertRedirect();

        $this->assertSame('lolos', $app->fresh()->status_partisipasi);

        $review = ClientReview::where('project_application_id', $app->id)->first();
        $this->assertNotNull($review);
        $this->assertSame('approve', $review->keputusan);
        $this->assertSame('B', $review->grade_client);
    }

    public function test_show_403_jika_client_tidak_diassign_ke_proyek(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $klien = $this->buatClient();
        $klienLain = $this->buatClient();
        $project = $this->buatProyek($admin);
        $project->update(['client_id' => $klienLain->id]);

        $this->actingAs($klien)->get(route('client.reviews.show', $project))->assertForbidden();
    }

    public function test_dashboard_client_tidak_tampilkan_honor_extras_pending(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $klien = $this->buatClient();
        $klienLain = $this->buatClient();

        $projectMilikClient = $this->buatProyek($admin);
        $projectMilikClient->update(['client_id' => $klien->id]);

        $projectLain = $this->buatProyek($admin);
        $projectLain->update(['client_id' => $klienLain->id]);

        $appMilikClient = $this->buatApplication($projectMilikClient, 'lolos');
        $appLain = $this->buatApplication($projectLain, 'lolos');

        Payment::create(['project_application_id' => $appMilikClient->id, 'status' => 'belum_dibayar']);
        Payment::create(['project_application_id' => $appLain->id, 'status' => 'belum_dibayar']);

        $this->actingAs($klien)->get(route('client.dashboard'))
            ->assertOk()
            ->assertDontSee('Pembayaran Pending')
            ->assertSee('Ajukan Proyek Pertama');
    }
}
