<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\CdReview;
use App\Models\ExtrasProfile;
use App\Models\Payment;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GreenlightKonsolidasiTest extends TestCase
{
    use RefreshDatabase;

    private function buatCd(): User
    {
        return User::factory()->create(['role' => 'casting_director']);
    }

    private function buatProyek(User $admin): CastingProject
    {
        return CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Test',
            'client_ph' => 'PH',
            'deadline' => now()->addDays(7),
            'kuota' => 10,
        ]);
    }

    private function buatApplication(CastingProject $project, string $status = 'diajukan_ke_cd'): ProjectApplication
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
        $admin = User::factory()->create(['role' => 'admin_default']);
        $cd = $this->buatCd();
        $project = $this->buatProyek($admin);
        $project->cdAssignments()->create(['cd_user_id' => $cd->id]);

        $this->buatApplication($project, 'diajukan_ke_cd');
        $this->buatApplication($project, 'lolos');
        $this->buatApplication($project, 'ditolak');

        $response = $this->actingAs($cd)->get(route('cd.reviews.index'));
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
        $admin = User::factory()->create(['role' => 'admin_default']);
        $cd = $this->buatCd();
        $project = $this->buatProyek($admin);
        $project->cdAssignments()->create(['cd_user_id' => $cd->id]);

        $appPending = $this->buatApplication($project, 'diajukan_ke_cd');
        $appLolos = $this->buatApplication($project, 'lolos');
        $appTolak = $this->buatApplication($project, 'ditolak');

        $response = $this->actingAs($cd)->get(route('cd.reviews.show', $project));
        $response->assertOk();

        $viewData = $response->original->getData();
        $ids = $viewData['applications']->pluck('id')->all();

        $this->assertContains($appPending->id, $ids);
        $this->assertContains($appLolos->id, $ids);
        $this->assertContains($appTolak->id, $ids);
    }

    public function test_show_filter_menunggu_hanya_tampilkan_diajukan_ke_cd(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $cd = $this->buatCd();
        $project = $this->buatProyek($admin);
        $project->cdAssignments()->create(['cd_user_id' => $cd->id]);

        $appPending = $this->buatApplication($project, 'diajukan_ke_cd');
        $appLolos = $this->buatApplication($project, 'lolos');

        $response = $this->actingAs($cd)->get(route('cd.reviews.show', ['castingProject' => $project, 'status' => 'menunggu']));
        $response->assertOk();

        $viewData = $response->original->getData();
        $ids = $viewData['applications']->pluck('id')->all();

        $this->assertContains($appPending->id, $ids);
        $this->assertNotContains($appLolos->id, $ids);
    }

    public function test_approve_dengan_grade_cd_tersimpan(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $cd = $this->buatCd();
        $project = $this->buatProyek($admin);
        $project->cdAssignments()->create(['cd_user_id' => $cd->id]);

        $app = $this->buatApplication($project, 'diajukan_ke_cd');

        $this->actingAs($cd)->post(route('cd.reviews.review'), [
            'application_ids' => [$app->id],
            'keputusan' => 'approve',
            'grade_cd' => 'B',
        ])->assertRedirect();

        $this->assertSame('lolos', $app->fresh()->status_partisipasi);

        $review = CdReview::where('project_application_id', $app->id)->first();
        $this->assertNotNull($review);
        $this->assertSame('approve', $review->keputusan);
        $this->assertSame('B', $review->grade_cd);
    }

    public function test_show_403_jika_cd_tidak_diassign_ke_proyek(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $cd = $this->buatCd();
        $cdLain = $this->buatCd();
        $project = $this->buatProyek($admin);
        $project->cdAssignments()->create(['cd_user_id' => $cdLain->id]);

        $this->actingAs($cd)->get(route('cd.reviews.show', $project))->assertForbidden();
    }

    public function test_dashboard_tampilkan_pelunasan_pending_scoped_ke_proyek_cd(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $cd = $this->buatCd();
        $cdLain = $this->buatCd();

        $projectMilikCd = $this->buatProyek($admin);
        $projectMilikCd->cdAssignments()->create(['cd_user_id' => $cd->id]);

        $projectLain = $this->buatProyek($admin);
        $projectLain->cdAssignments()->create(['cd_user_id' => $cdLain->id]);

        $appMilikCd = $this->buatApplication($projectMilikCd, 'lolos');
        $appLain = $this->buatApplication($projectLain, 'lolos');

        Payment::create(['project_application_id' => $appMilikCd->id, 'status' => 'belum_dibayar']);
        Payment::create(['project_application_id' => $appLain->id, 'status' => 'belum_dibayar']);

        $response = $this->actingAs($cd)->get(route('cd.dashboard'));
        $response->assertOk();

        $viewData = $response->original->getData();
        $pending = $viewData['pelunasanPending'];

        // Hanya payment dari proyek milik CD ini yang muncul
        $this->assertSame(1, $pending->count());
        $this->assertSame(1, $pending->first());
    }
}
