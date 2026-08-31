<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SPEC.md Bagian E: CD hanya boleh akses invoice & review kandidat proyek
 * yang dia di-assign. Regresi: CD yang memang di-assign tetap normal.
 */
class CdProjectAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function buatProyek(User $admin): CastingProject
    {
        return CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Test',
            'client_ph' => 'PH Test',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ]);
    }

    private function buatApplicationDiajukanKeCd(CastingProject $project): ProjectApplication
    {
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);

        return ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'diajukan_ke_cd',
            'fee_final' => 200000,
        ]);
    }

    public function test_admin_default_bisa_assign_cd_ke_proyek(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $cd = User::factory()->create(['role' => 'casting_director']);
        $project = $this->buatProyek($admin);

        $response = $this->actingAs($admin)->post(route('admin.projects.assign-cd', $project), [
            'cd_user_id' => $cd->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cd_project_assignments', [
            'casting_project_id' => $project->id,
            'cd_user_id' => $cd->id,
        ]);
    }

    public function test_satu_proyek_bisa_punya_multiple_cd_assigned(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $cdA = User::factory()->create(['role' => 'casting_director']);
        $cdB = User::factory()->create(['role' => 'casting_director']);
        $project = $this->buatProyek($admin);

        $this->actingAs($admin)->post(route('admin.projects.assign-cd', $project), ['cd_user_id' => $cdA->id]);
        $this->actingAs($admin)->post(route('admin.projects.assign-cd', $project), ['cd_user_id' => $cdB->id]);

        $this->assertSame(2, $project->cdAssignments()->count());
    }

    public function test_assign_cd_ditolak_untuk_user_bukan_casting_director(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $bukanCd = User::factory()->create(['role' => 'extras']);
        $project = $this->buatProyek($admin);

        $this->actingAs($admin)->post(route('admin.projects.assign-cd', $project), [
            'cd_user_id' => $bukanCd->id,
        ])->assertStatus(422);
    }

    public function test_assign_cd_duplikat_tidak_bikin_record_ganda(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $cd = User::factory()->create(['role' => 'casting_director']);
        $project = $this->buatProyek($admin);

        $this->actingAs($admin)->post(route('admin.projects.assign-cd', $project), ['cd_user_id' => $cd->id]);
        $this->actingAs($admin)->post(route('admin.projects.assign-cd', $project), ['cd_user_id' => $cd->id]);

        $this->assertSame(1, $project->cdAssignments()->count());
    }

    public function test_cd_yang_diassign_tetap_bisa_akses_invoice(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $cdA = User::factory()->create(['role' => 'casting_director']);
        $project1 = $this->buatProyek($admin);
        $project1->cdAssignments()->create(['cd_user_id' => $cdA->id]);

        $this->actingAs($cdA)->get(route('invoices.show', $project1))->assertOk();
    }

    public function test_cd_yang_tidak_diassign_ditolak_akses_invoice(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $cdA = User::factory()->create(['role' => 'casting_director']);
        $cdB = User::factory()->create(['role' => 'casting_director']);
        $project1 = $this->buatProyek($admin);
        $project2 = $this->buatProyek($admin);
        $project1->cdAssignments()->create(['cd_user_id' => $cdA->id]);
        $project2->cdAssignments()->create(['cd_user_id' => $cdB->id]);

        $this->actingAs($cdB)->get(route('invoices.show', $project1))->assertStatus(403);
    }

    public function test_cd_tanpa_assignment_sama_sekali_ditolak_akses_invoice(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $cdTanpaProyek = User::factory()->create(['role' => 'casting_director']);
        $project1 = $this->buatProyek($admin);

        $this->actingAs($cdTanpaProyek)->get(route('invoices.show', $project1))->assertStatus(403);
    }

    public function test_cd_yang_diassign_bisa_lihat_dan_approve_kandidat_proyeknya(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $cdA = User::factory()->create(['role' => 'casting_director']);
        $project1 = $this->buatProyek($admin);
        $project1->cdAssignments()->create(['cd_user_id' => $cdA->id]);
        $application = $this->buatApplicationDiajukanKeCd($project1);

        $this->actingAs($cdA)->get(route('cd.reviews.index'))->assertOk()->assertSee('Alias Test');

        $this->actingAs($cdA)->post(route('cd.reviews.review'), [
            'application_ids' => [$application->id],
            'keputusan' => 'approve',
        ])->assertRedirect();

        $this->assertSame('lolos', $application->fresh()->status_partisipasi);
    }

    public function test_cd_yang_tidak_diassign_tidak_lihat_kandidat_proyek_lain(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $cdA = User::factory()->create(['role' => 'casting_director']);
        $cdB = User::factory()->create(['role' => 'casting_director']);
        $project1 = $this->buatProyek($admin);
        $project2 = $this->buatProyek($admin);
        $project1->cdAssignments()->create(['cd_user_id' => $cdA->id]);
        $project2->cdAssignments()->create(['cd_user_id' => $cdB->id]);
        $application = $this->buatApplicationDiajukanKeCd($project1);

        $this->actingAs($cdB)->get(route('cd.reviews.index'))->assertOk()->assertDontSee('Alias Test');
    }

    public function test_cd_yang_tidak_diassign_aksi_approve_tidak_berefek(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $cdA = User::factory()->create(['role' => 'casting_director']);
        $cdB = User::factory()->create(['role' => 'casting_director']);
        $project1 = $this->buatProyek($admin);
        $project1->cdAssignments()->create(['cd_user_id' => $cdA->id]);
        $application = $this->buatApplicationDiajukanKeCd($project1);

        $this->actingAs($cdB)->post(route('cd.reviews.review'), [
            'application_ids' => [$application->id],
            'keputusan' => 'approve',
        ])->assertRedirect();

        $this->assertSame('diajukan_ke_cd', $application->fresh()->status_partisipasi);
    }

    public function test_cd_tanpa_assignment_sama_sekali_tidak_bisa_approve(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $cdTanpaProyek = User::factory()->create(['role' => 'casting_director']);
        $project1 = $this->buatProyek($admin);
        $application = $this->buatApplicationDiajukanKeCd($project1);

        $this->actingAs($cdTanpaProyek)->post(route('cd.reviews.review'), [
            'application_ids' => [$application->id],
            'keputusan' => 'approve',
        ])->assertRedirect();

        $this->assertSame('diajukan_ke_cd', $application->fresh()->status_partisipasi);
    }
}
